<?php
function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function url($value) { return $value; }
function media_url($value) { return $value; }
function csrf_field() { return ''; }
set_error_handler(function ($severity, $message) { throw new RuntimeException($message); });
$isCertificatePreview = true;
$isManagedCertificate = true;
$certificate = ['student_name' => 'Estudante Exemplo', 'verification_code' => 'ABC123'];
$certificateText = 'Certificamos a participação do estudante.';
$certificateStatus = ['frequency' => 100];
$certificateProgram = [];
foreach ([0, 1] as $back) foreach ([0, 1] as $hide) foreach ([0, 1] as $program) {
    $course = ['id' => 1, 'title' => 'Curso', 'teacher_name' => 'Professor Teste', 'certificate_responsible_name' => 'Responsável Teste', 'certificate_responsible_credential' => 'Formação Teste', 'certificate_footer_on_back' => $back, 'certificate_hide_responsible' => $hide, 'certificate_program_enabled' => $program];
    ob_start();
    require dirname(__DIR__) . '/app/Views/admin/education/certificate.php';
    $html = ob_get_clean();
    if (substr_count($html, '<footer class="education-certificate-footnote') !== 1) throw new RuntimeException('Rodapé duplicado ou ausente');
    $footer = strpos($html, '<footer class="education-certificate-footnote');
    $verso = strpos($html, '<article class="education-certificate-sheet education-certificate-program-sheet');
    if ($back && ($verso === false || $footer < $verso)) throw new RuntimeException('Rodapé fora do verso');
    if (!$back && $verso !== false && $footer > $verso) throw new RuntimeException('Rodapé fora da frente');
    if (str_contains($html, 'Professor Teste') === (bool) $hide) throw new RuntimeException('Visibilidade do professor incorreta');
    if (str_contains($html, 'Responsável Teste') !== (!$hide && (bool) $program)) throw new RuntimeException('Visibilidade do responsável incorreta');
    if (str_contains($html, 'Formação Teste') !== (!$hide && (bool) $program)) throw new RuntimeException('Visibilidade da formação incorreta');
}
echo "8 combinações de posição do rodapé e visibilidade do responsável aprovadas.\n";
