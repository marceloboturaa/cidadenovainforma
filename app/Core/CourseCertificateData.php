<?php

namespace App\Core;

class CourseCertificateData
{
    public static function validate(array $data): void
    {
        foreach (['starts_at', 'ends_at'] as $field) {
            $value = trim((string) ($data[$field] ?? ''));
            if ($value === '') continue;
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if (!$date || $date->format('Y-m-d') !== $value) {
                throw new \InvalidArgumentException('Informe datas válidas para o início e o término do curso.');
            }
        }
        if (!empty($data['starts_at']) && !empty($data['ends_at']) && $data['starts_at'] > $data['ends_at']) {
            throw new \InvalidArgumentException('O término do curso deve ser igual ou posterior ao início.');
        }
        $hours = str_replace(',', '.', trim((string) ($data['workload_hours'] ?? '')));
        if ($hours !== '' && (!is_numeric($hours) || (float) $hours <= 0 || (float) $hours > 9999.99)) {
            throw new \InvalidArgumentException('Informe uma carga horária maior que zero e de até 9999,99 horas.');
        }
    }

    public static function hours(array $course): string
    {
        if (empty($course['workload_hours'])) return '';
        return rtrim(rtrim(number_format((float) str_replace(',', '.', (string) $course['workload_hours']), 2, ',', ''), '0'), ',');
    }
}
