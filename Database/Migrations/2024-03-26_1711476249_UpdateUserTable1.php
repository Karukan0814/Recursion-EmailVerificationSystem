<?php

namespace Database\Migrations;

use Database\SchemaMigration;

class UpdateUserTable2 implements SchemaMigration
{
    public function up(): array
    {
        return [
            "ALTER TABLE users MODIFY COLUMN email_confirmed_at DATETIME"
        ];
    }

    public function down(): array
    {
        return [
            "ALTER TABLE users MODIFY COLUMN email_confirmed_at VARCHAR(255)"
        ];
    }
}