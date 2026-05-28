<?php

namespace App\Models;

use App\Core\Database;

class Permission
{
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT id, name, slug FROM permissions ORDER BY slug ASC')
            ->fetchAll();
    }
}
