<?php

namespace Entity;

use ObjectPeer\UserPeer;
use PS\Core\Database\Fields\IntegerField;
use PS\Core\Database\Entity;
use PS\Core\Database\Fields\StringField;

class SessionEntity extends Entity
{
    protected function setEntitname(): string
    {
        return 'Session';
    }

    protected function setTabelName(): string
    {
        return 'sessions';
    }

    public function fieldDefinition(): array
    {
        $userID = (new IntegerField('UserID'))
            ->setNotNullable(false)
            ->setRequired(true)
            ->setForeignKey('users', 'ID');
        $sessionToken = (new StringField('sessionToken'))
            ->setNotNullable(false)
            ->setLength(255);

        return [
            $userID,
            $sessionToken
        ];
    }
}
