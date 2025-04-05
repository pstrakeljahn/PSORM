<?php

namespace Entity;

use PS\Core\Database\Fields\IntegerField;
use PS\Core\Database\Entity;
use PS\Core\Database\Fields\StringField;

class SessionEntity extends Entity
{
    public bool $withoutMeta = true;

    protected function setEntityName(): string
    {
        return 'Session';
    }

    protected function setTableName(): string
    {
        return 'sessions';
    }

    protected function apiDisabled(): bool
    {
        return true;
    }

    public function fieldDefinition(): array
    {
        $userID = (new IntegerField('UserID'))
            ->setNotNullable(false)
            ->setRequired(true)
            ->setForeignKey('users', 'ID');
        $sessionToken = (new StringField('refreshToken'))
            ->setNotNullable(false)
            ->setLength(500);

        return [
            $userID,
            $sessionToken
        ];
    }
}
