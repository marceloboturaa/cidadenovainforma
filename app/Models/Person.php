<?php

namespace App\Models;

use App\Core\Database;

class Person
{
    public static function all(string $query = ''): array
    {
        $sql = 'SELECT people.*, creator.name AS creator_name
                FROM people
                LEFT JOIN users creator ON creator.id = people.created_by
                WHERE people.active = 1';
        $params = [];

        if ($query !== '') {
            $sql .= ' AND (people.full_name LIKE :query OR people.email LIKE :query OR people.whatsapp LIKE :query OR people.phone LIKE :query OR people.cpf LIKE :query)';
            $params['query'] = '%' . $query . '%';
        }

        $sql .= ' ORDER BY people.full_name ASC';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM people WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO people
                (full_name, cpf, birth_date, phone, whatsapp, email, address, district, guardian_name, contact_authorized, notes, active, created_by, updated_by, created_at, updated_at)
             VALUES
                (:full_name, :cpf, :birth_date, :phone, :whatsapp, :email, :address, :district, :guardian_name, :contact_authorized, :notes, 1, :created_by, :updated_by, NOW(), NOW())'
        );
        $stmt->execute(self::payload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $payload = self::payload($data);
        $payload['id'] = $id;

        $stmt = Database::connection()->prepare(
            'UPDATE people
             SET full_name = :full_name,
                 cpf = :cpf,
                 birth_date = :birth_date,
                 phone = :phone,
                 whatsapp = :whatsapp,
                 email = :email,
                 address = :address,
                 district = :district,
                 guardian_name = :guardian_name,
                 contact_authorized = :contact_authorized,
                 notes = :notes,
                 updated_by = :updated_by,
                 updated_at = NOW()
             WHERE id = :id'
        );
        unset($payload['created_by']);
        $stmt->execute($payload);
    }

    public static function deactivate(int $id): void
    {
        Database::connection()
            ->prepare('UPDATE people SET active = 0, updated_at = NOW() WHERE id = :id')
            ->execute(['id' => $id]);
    }

    private static function payload(array $data): array
    {
        $birthDate = trim((string) ($data['birth_date'] ?? ''));

        return [
            'full_name' => trim((string) ($data['full_name'] ?? '')),
            'cpf' => self::nullable($data['cpf'] ?? null),
            'birth_date' => $birthDate !== '' ? $birthDate : null,
            'phone' => self::nullable($data['phone'] ?? null),
            'whatsapp' => self::nullable($data['whatsapp'] ?? null),
            'email' => self::nullable($data['email'] ?? null),
            'address' => self::nullable($data['address'] ?? null),
            'district' => self::nullable($data['district'] ?? null),
            'guardian_name' => self::nullable($data['guardian_name'] ?? null),
            'contact_authorized' => (int) !empty($data['contact_authorized']),
            'notes' => self::nullable($data['notes'] ?? null),
            'created_by' => $data['created_by'] ?? null,
            'updated_by' => $data['updated_by'] ?? null,
        ];
    }

    private static function nullable(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value !== '' ? $value : null;
    }
}
