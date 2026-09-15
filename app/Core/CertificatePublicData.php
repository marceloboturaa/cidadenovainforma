<?php
namespace App\Core;

class CertificatePublicData
{
    public static function project(?array $certificate): ?array
    {
        if (!$certificate || !in_array($certificate['status'] ?? '', ['issued', 'revoked'], true)) return null;
        if ($certificate['status'] === 'revoked') return ['status' => 'revoked'];
        $words = preg_split('/\s+/u', trim((string) ($certificate['student_name'] ?? '')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = array_map(static fn ($word) => mb_strtoupper(mb_substr($word, 0, 1, 'UTF-8'), 'UTF-8') . '.', $words);
        return [
            'status' => 'issued',
            'document_label' => ($certificate['certificate_activity_type'] ?? '') === 'evento'
                ? (($certificate['certificate_type'] ?? '') === 'coordenacao' ? 'Certificado de coordenação' : 'Certificado de participação')
                : (($certificate['certificate_activity_type'] ?? '') === 'reconhecimento' ? 'Certificado de reconhecimento' : 'Certificado de curso'),
            'recipient_initials' => $initials ? implode(' ', $initials) : 'Identificação preservada',
            'course_title' => (string) ($certificate['course_title'] ?? ''),
            'certificate_activity_type' => (string) ($certificate['certificate_activity_type'] ?? ''),
            'issued_at' => $certificate['issued_at'] ?? null,
            'verification_code' => (string) ($certificate['verification_code'] ?? ''),
            'institution_name' => trim((string) ($certificate['certificate_institution_name'] ?? '')) ?: 'Cidade Nova Informa',
        ];
    }
}
