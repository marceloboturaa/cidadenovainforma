<?php

namespace App\Models;

use App\Core\Database;

class Person
{
    public static function all(string $query = '', ?int $createdBy = null): array
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

        if ($createdBy !== null) {
            $sql .= ' AND people.created_by = :created_by';
            $params['created_by'] = $createdBy;
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
                (full_name, cpf, birth_date, phone, whatsapp, email, cep, address, address_number, address_complement, district, city, state, is_minor, guardian_name, guardian_relation, guardian_cpf, guardian_phone, guardian_email, contact_authorized, notes, active, created_by, updated_by, created_at, updated_at)
             VALUES
                (:full_name, :cpf, :birth_date, :phone, :whatsapp, :email, :cep, :address, :address_number, :address_complement, :district, :city, :state, :is_minor, :guardian_name, :guardian_relation, :guardian_cpf, :guardian_phone, :guardian_email, :contact_authorized, :notes, 1, :created_by, :updated_by, NOW(), NOW())'
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
                 cep = :cep,
                 address = :address,
                 address_number = :address_number,
                 address_complement = :address_complement,
                 district = :district,
                 city = :city,
                 state = :state,
                 is_minor = :is_minor,
                 guardian_name = :guardian_name,
                 guardian_relation = :guardian_relation,
                 guardian_cpf = :guardian_cpf,
                 guardian_phone = :guardian_phone,
                 guardian_email = :guardian_email,
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
            'cep' => self::nullable($data['cep'] ?? null),
            'address' => self::nullable($data['address'] ?? null),
            'address_number' => self::nullable($data['address_number'] ?? null),
            'address_complement' => self::nullable($data['address_complement'] ?? null),
            'district' => self::nullable($data['district'] ?? null),
            'city' => self::nullable($data['city'] ?? null),
            'state' => self::nullable($data['state'] ?? null),
            'is_minor' => (int) !empty($data['is_minor']),
            'guardian_name' => self::nullable($data['guardian_name'] ?? null),
            'guardian_relation' => self::nullable($data['guardian_relation'] ?? null),
            'guardian_cpf' => self::nullable($data['guardian_cpf'] ?? null),
            'guardian_phone' => self::nullable($data['guardian_phone'] ?? null),
            'guardian_email' => self::nullable($data['guardian_email'] ?? null),
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
