<?php

namespace App\Controllers\Admin;

use App\Core\Csrf;
use App\Core\Middleware;

class LatexController
{
    private const MAX_TEX_SIZE = 1024 * 1024;
    private const TIMEOUT_SECONDS = 30;

    public function compile(): void
    {
        Middleware::auth();

        if (!Csrf::validate($_POST['_token'] ?? null)) {
            $this->jsonError('Sessao expirada. Atualize a pagina e tente novamente.', 419);
        }

        $tex = $this->texFromRequest();
        if ($tex === '') {
            $this->jsonError('Envie o conteudo LaTeX no campo tex ou um arquivo .tex.');
        }

        if (strlen($tex) > self::MAX_TEX_SIZE) {
            $this->jsonError('O arquivo LaTeX deve ter no maximo 1MB.');
        }

        if ($message = $this->unsafeLatexMessage($tex)) {
            $this->jsonError($message);
        }

        $compiler = $this->compiler();
        if ($compiler === null) {
            $this->jsonError('Nenhum compilador LaTeX encontrado. Instale MiKTeX/TeX Live ou configure LATEX_COMPILER no .env.', 500);
        }

        $workDir = dirname(__DIR__, 3) . '/storage/temp/latex-' . bin2hex(random_bytes(8));
        if (!is_dir($workDir) && !mkdir($workDir, 0775, true)) {
            $this->jsonError('Nao foi possivel criar a pasta temporaria da compilacao.', 500);
        }

        $texPath = $workDir . '/main.tex';
        file_put_contents($texPath, $tex);

        $result = $this->runCompiler($compiler, $workDir);
        $pdfPath = $workDir . '/main.pdf';

        if (!$result['success'] || !is_file($pdfPath)) {
            $log = is_file($workDir . '/main.log')
                ? (string) file_get_contents($workDir . '/main.log')
                : $result['output'];
            $this->cleanup($workDir);
            $this->jsonError('Falha ao compilar o LaTeX.', 422, [
                'log' => $this->shortLog($log),
            ]);
        }

        $pdf = (string) file_get_contents($pdfPath);
        $this->cleanup($workDir);

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="latex-compilado.pdf"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }

    private function texFromRequest(): string
    {
        if (isset($_POST['tex'])) {
            return trim((string) $_POST['tex']);
        }

        if (!empty($_FILES['tex_file']['tmp_name']) && ($_FILES['tex_file']['error'] ?? UPLOAD_ERR_OK) === UPLOAD_ERR_OK) {
            $name = (string) ($_FILES['tex_file']['name'] ?? '');
            if (!str_ends_with(strtolower($name), '.tex')) {
                $this->jsonError('Envie um arquivo com extensao .tex.');
            }

            return trim((string) file_get_contents((string) $_FILES['tex_file']['tmp_name']));
        }

        return '';
    }

    private function unsafeLatexMessage(string $tex): ?string
    {
        $blocked = [
            '\\write18' => 'O comando \\write18 nao e permitido.',
            '\\openout' => 'O comando \\openout nao e permitido.',
            '\\openin' => 'O comando \\openin nao e permitido.',
            '\\read' => 'O comando \\read nao e permitido.',
            '\\catcode' => 'O comando \\catcode nao e permitido.',
        ];

        foreach ($blocked as $command => $message) {
            if (stripos($tex, $command) !== false) {
                return $message;
            }
        }

        if (preg_match('/\\\\(?:input|include|includegraphics)(?:\[[^\]]*])?\{(?:[a-z]:|\/|\\\\|\.\.)/i', $tex)) {
            return 'Caminhos absolutos ou com .. nao sao permitidos em input/include/includegraphics.';
        }

        return null;
    }

    private function compiler(): ?array
    {
        $configured = trim((string) getenv('LATEX_COMPILER'));
        $candidates = array_filter([
            $configured !== '' ? $configured : null,
            'latexmk',
            'pdflatex',
            'xelatex',
        ]);

        foreach ($candidates as $candidate) {
            if ($this->commandExists($candidate)) {
                $name = strtolower(basename($candidate, '.exe'));
                return [
                    'path' => $candidate,
                    'name' => $name,
                ];
            }
        }

        return null;
    }

    private function commandExists(string $command): bool
    {
        if (is_file($command)) {
            return true;
        }

        $checkCommand = stripos(PHP_OS_FAMILY, 'Windows') === 0
            ? ['where', $command]
            : ['which', $command];

        $process = @proc_open($checkCommand, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (!is_resource($process)) {
            return false;
        }

        foreach ($pipes as $pipe) {
            fclose($pipe);
        }

        return proc_close($process) === 0;
    }

    private function runCompiler(array $compiler, string $workDir): array
    {
        if ($compiler['name'] === 'latexmk') {
            $command = [
                $compiler['path'],
                '-pdf',
                '-interaction=nonstopmode',
                '-halt-on-error',
                '-file-line-error',
                '-shell-escape-',
                'main.tex',
            ];
        } else {
            $command = [
                $compiler['path'],
                '-interaction=nonstopmode',
                '-halt-on-error',
                '-file-line-error',
                '-no-shell-escape',
                'main.tex',
            ];
        }

        return $this->runProcess($command, $workDir);
    }

    private function runProcess(array $command, string $cwd): array
    {
        $process = @proc_open($command, [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes, $cwd);

        if (!is_resource($process)) {
            return [
                'success' => false,
                'output' => 'Nao foi possivel iniciar o compilador.',
            ];
        }

        foreach ($pipes as $pipe) {
            stream_set_blocking($pipe, false);
        }

        $output = '';
        $startedAt = time();

        do {
            $status = proc_get_status($process);
            $output .= stream_get_contents($pipes[1]);
            $output .= stream_get_contents($pipes[2]);

            if (time() - $startedAt > self::TIMEOUT_SECONDS) {
                proc_terminate($process);
                foreach ($pipes as $pipe) {
                    fclose($pipe);
                }

                proc_close($process);

                return [
                    'success' => false,
                    'output' => $output . "\nTempo limite excedido.",
                ];
            }

            usleep(100000);
        } while ($status['running']);

        foreach ($pipes as $pipe) {
            $output .= stream_get_contents($pipe);
            fclose($pipe);
        }

        $exitCode = proc_close($process);

        return [
            'success' => $exitCode === 0,
            'output' => $output,
        ];
    }

    private function shortLog(string $log): string
    {
        $log = preg_replace('/\R{3,}/', "\n\n", $log) ?? $log;
        $lines = preg_split('/\R/', trim($log)) ?: [];

        return implode("\n", array_slice($lines, -80));
    }

    private function cleanup(string $directory): void
    {
        $root = realpath(dirname(__DIR__, 3) . '/storage/temp');
        $target = realpath($directory);

        if (!$root || !$target || !str_starts_with($target, $root)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($target);
    }

    private function jsonError(string $message, int $status = 422, array $extra = []): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message] + $extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
