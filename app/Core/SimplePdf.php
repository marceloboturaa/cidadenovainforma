<?php

namespace App\Core;

class SimplePdf
{
    private const PAGE_WIDTH = 595;
    private const PAGE_HEIGHT = 842;
    private const LEFT = 42;
    private const TOP = 800;
    private const LINE_HEIGHT = 14;

    public static function fromLines(string $title, array $lines): string
    {
        $pages = [];
        $current = [];
        $cursor = self::TOP;

        $addLine = function (string $line, int $size = 10) use (&$pages, &$current, &$cursor): void {
            if ($cursor < 46) {
                $pages[] = $current;
                $current = [];
                $cursor = self::TOP;
            }

            $current[] = ['text' => $line, 'size' => $size, 'y' => $cursor];
            $cursor -= $size >= 14 ? 20 : self::LINE_HEIGHT;
        };

        $addLine($title, 16);
        $addLine('Gerado em ' . date('d/m/Y H:i'), 9);
        $addLine('', 10);

        foreach ($lines as $line) {
            foreach (self::wrap((string) $line, 92) as $wrapped) {
                $addLine($wrapped, 9);
            }
        }

        if ($current) {
            $pages[] = $current;
        }

        return self::build($pages ?: [[]]);
    }

    public static function registrationReport(array $event, array $participants): string
    {
        $title = 'Lista de Inscrições';
        $eventTitle = (string) ($event['title'] ?? 'Evento');
        $pages = [];
        $current = [];
        $cursor = self::TOP;
        $count = count($participants);

        $add = function (string $text, int $size = 9, int $gap = 14) use (&$pages, &$current, &$cursor): void {
            if ($cursor < 70) {
                $pages[] = $current;
                $current = [];
                $cursor = self::TOP;
            }
            $current[] = ['text' => $text, 'size' => $size, 'y' => $cursor];
            $cursor -= $gap;
        };

        $rule = function () use (&$current, &$cursor): void {
            $current[] = ['type' => 'rule', 'y' => $cursor + 5];
            $cursor -= 8;
        };

        $add($title, 18, 22);
        $add('Evento: ' . $eventTitle, 12, 18);
        $add('Gerado em ' . date('d/m/Y H:i') . ' | Total: ' . $count . ' inscrição(ões)', 9, 16);
        if (!empty($event['starts_at'])) {
            $add('Data: ' . date('d/m/Y H:i', strtotime((string) $event['starts_at'])), 9, 13);
        }
        $add('Local: ' . (($event['location'] ?? '') ?: '-'), 9, 13);
        $add('Endereço: ' . (($event['event_address'] ?? '') ?: '-'), 9, 16);
        $rule();

        if (!$participants) {
            $add('Nenhuma inscrição encontrada.', 10, 16);
        }

        foreach ($participants as $index => $participant) {
            $status = ucfirst((string) ($participant['status'] ?? 'pendente'));
            $name = (string) ($participant['full_name'] ?? '');
            $contact = ($participant['whatsapp'] ?? '') ?: (($participant['phone'] ?? '') ?: '-');
            $email = ($participant['email'] ?? '') ?: '-';
            $city = trim((string) (($participant['district'] ?? '') . ' / ' . ($participant['city'] ?? '')), ' /') ?: '-';
            $image = !empty($participant['image_authorized']) ? 'Sim' : 'Não';
            $heardAbout = ($participant['heard_about'] ?? '') ?: '-';
            $guardian = !empty($participant['is_minor'])
                ? trim((string) (($participant['guardian_name'] ?? '-') . ' | ' . ($participant['guardian_phone'] ?? '-')), ' |')
                : '-';

            $add(($index + 1) . '. ' . $name . '  [' . $status . ']', 11, 17);
            $add('CPF: ' . (($participant['cpf'] ?? '') ?: '-') . '    Nascimento: ' . (($participant['birth_date'] ?? '') ?: '-') . '    Contato: ' . $contact, 8, 12);
            $add('E-mail: ' . $email, 8, 12);
            $add('Bairro/Cidade: ' . $city . '    Uso de imagem: ' . $image, 8, 12);
            $add('Como soube do evento: ' . $heardAbout, 8, 12);
            if (!empty($participant['event_expectations'])) {
                foreach (self::wrap('O que espera do evento: ' . (string) $participant['event_expectations'], 96) as $line) {
                    $add($line, 8, 11);
                }
            }
            if (!empty($participant['is_minor'])) {
                $add('Responsável: ' . $guardian . '    Parentesco: ' . (($participant['guardian_relation'] ?? '') ?: '-'), 8, 12);
            }
            if (!empty($participant['notes'])) {
                foreach (self::wrap('Obs.: ' . (string) $participant['notes'], 96) as $line) {
                    $add($line, 8, 11);
                }
            }
            $add('Assinatura: _______________________________________________', 8, 16);
            $rule();
        }

        if ($current) {
            $pages[] = $current;
        }

        return self::build($pages ?: [[]]);
    }

    public static function attendanceReport(array $event, array $participants): string
    {
        $pages = [];
        $current = [];
        $cursor = self::TOP;
        $eventTitle = (string) ($event['title'] ?? 'Evento');

        $add = function (string $text, int $size = 9, int $gap = 14) use (&$pages, &$current, &$cursor): void {
            if ($cursor < 70) {
                $pages[] = $current;
                $current = [];
                $cursor = self::TOP;
            }
            $current[] = ['text' => $text, 'size' => $size, 'y' => $cursor];
            $cursor -= $gap;
        };

        $rule = function () use (&$current, &$cursor): void {
            $current[] = ['type' => 'rule', 'y' => $cursor + 5];
            $cursor -= 8;
        };

        $add('Lista de Chamada e Presença', 18, 22);
        $add('Evento: ' . $eventTitle, 12, 18);
        if (!empty($event['starts_at'])) {
            $add('Data: ' . date('d/m/Y H:i', strtotime((string) $event['starts_at'])), 9, 13);
        }
        $add('Local: ' . (($event['location'] ?? '') ?: '-'), 9, 13);
        $add('Total na lista: ' . count($participants), 9, 18);
        $rule();

        if (!$participants) {
            $add('Nenhum participante encontrado.', 10, 16);
        }

        foreach ($participants as $index => $participant) {
            $name = (string) ($participant['full_name'] ?? '');
            $status = ucfirst((string) ($participant['status'] ?? 'inscrito'));
            $contact = ($participant['whatsapp'] ?? '') ?: (($participant['phone'] ?? '') ?: '-');

            $add(($index + 1) . '. ' . $name . '  [' . $status . ']', 10, 16);
            $add('Contato: ' . $contact . '    Presença: [   ] Sim  [   ] Não    Horário: ________', 8, 14);
            $add('Assinatura: ______________________________________________________________', 8, 17);
            $rule();
        }

        if ($current) {
            $pages[] = $current;
        }

        return self::build($pages ?: [[]]);
    }

    public static function participantNamesReport(array $event, array $participants): string
    {
        $eventTitle = (string) ($event['title'] ?? 'Cursinho');
        $lines = [
            'Curso/evento: ' . $eventTitle,
            'Total de inscritos: ' . count($participants),
            '',
        ];

        foreach ($participants as $index => $participant) {
            $lines[] = ($index + 1) . '. ' . (string) ($participant['full_name'] ?? '');
        }

        return self::fromLines(
            'Lista de inscritos',
            count($participants) > 0 ? $lines : array_merge($lines, ['Nenhum inscrito encontrado.'])
        );
    }

    private static function wrap(string $text, int $width): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');

        if ($text === '') {
            return [''];
        }

        return explode("\n", wordwrap($text, $width, "\n", true));
    }

    private static function build(array $pages): string
    {
        $objects = [];
        $pageIds = [];

        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';

        foreach ($pages as $page) {
            $content = "";
            foreach ($page as $line) {
                if (($line['type'] ?? '') === 'rule') {
                    $content .= "0.82 w\n42 " . (int) $line['y'] . " m\n553 " . (int) $line['y'] . " l\nS\n";
                    continue;
                }
                $content .= "BT\n";
                $content .= '/F1 ' . (int) $line['size'] . " Tf\n";
                $content .= self::LEFT . ' ' . (int) $line['y'] . " Td\n";
                $content .= '(' . self::escape($line['text']) . ") Tj\n";
                $content .= '-' . self::LEFT . " 0 Td\n";
                $content .= "ET\n";
            }

            $streamId = count($objects) + 1;
            $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";

            $pageId = count($objects) + 1;
            $pageIds[] = $pageId;
            $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $streamId . ' 0 R >>';
        }

        $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', array_map(fn (int $id): string => $id . ' 0 R', $pageIds)) . '] /Count ' . count($pageIds) . ' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $offsets[$index + 1] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1, $total = count($objects); $i <= $total; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }

    private static function escape(string $text): string
    {
        $encoded = iconv('UTF-8', 'WINDOWS-1252//TRANSLIT//IGNORE', $text);
        $encoded = $encoded !== false ? $encoded : $text;

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $encoded);
    }
}
