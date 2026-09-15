<?php
require dirname(__DIR__) . '/app/Core/CertificatePublicData.php';
use App\Core\CertificatePublicData;
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
function e($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function url($value) { return $value; }
set_error_handler(function ($severity, $message) { throw new RuntimeException($message); });
$source = ['status' => 'issued', 'student_name' => 'Ágata Silva Oliveira', 'student_email' => 'private@example.test', 'cpf' => '12345678900', 'teacher_name' => 'Professor Particular', 'revoked_reason' => 'Motivo privado', 'course_title' => '<script>alert(1)</script>', 'verification_code' => 'ABC123456789', 'issued_at' => '2026-09-14', 'certificate_activity_type' => 'evento', 'certificate_type' => 'coordenacao'];
$certificate = CertificatePublicData::project($source);
check($certificate['recipient_initials'] === 'Á. S. O.', 'Unicode initials');
foreach (['student_name', 'student_email', 'cpf', 'teacher_name', 'revoked_reason'] as $field) check(!array_key_exists($field, $certificate), 'Private field excluded: ' . $field);
$code = 'ABC123456789';
ob_start(); require dirname(__DIR__) . '/app/Views/public/certificate-verify.php'; $html = ob_get_clean();
foreach (['Ágata Silva Oliveira', 'private@example.test', 'Professor Particular', 'Motivo privado', '<script>alert(1)</script>'] as $private) check(!str_contains($html, $private), 'Private or unsafe HTML leaked');
check(str_contains($html, 'Certificado de coordenação'), 'Event type preserved');
foreach (['pending', 'draft', 'deleted'] as $status) check(CertificatePublicData::project(array_merge($source, ['status' => $status])) === null, 'Unpublished records hidden');
$certificate = CertificatePublicData::project(array_merge($source, ['status' => 'revoked']));
check($certificate === ['status' => 'revoked'], 'Revocation reveals status only');
ob_start(); require dirname(__DIR__) . '/app/Views/public/certificate-verify.php'; $html = ob_get_clean();
check(!str_contains($html, 'Á. S. O.') && str_contains($html, 'Certificado revogado'), 'Revoked view preserves privacy');
$certificate = null; $code = '';
ob_start(); require dirname(__DIR__) . '/app/Views/public/certificate-verify.php'; ob_end_clean();
echo "Verificação: dados mínimos, iniciais, estados privados e escape de HTML aprovados.\n";
