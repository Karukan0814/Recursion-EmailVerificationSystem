<?php

namespace Database\Migrations;

use Database\SchemaMigration;

class CreateHeadTable1 implements SchemaMigration
{
    public function up(): array
    {
        return [
            "CREATE TABLE heads (
                id BIGINT PRIMARY KEY AUTO_INCREMENT,
                character_id BIGINT UNIQUE,
                eye INT,
                nose INT,
                chin INT,
                hair INT,
                eyebrows INT,
                hair_color INT,
                FOREIGN KEY (character_id) REFERENCES characters(id) ON DELETE CASCADE
            )"
        ];
    }

    public function down(): array
    {
        return [
            "DROP TABLE heads"
        ];
    }
}