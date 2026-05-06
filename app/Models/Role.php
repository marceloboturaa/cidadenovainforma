<?php

namespace App\Models;

use App\Core\Database;

class Role
{
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT id, name, slug FROM roles ORDER BY level DESC, name ASC')
            ->fetchAll();
    }
}
