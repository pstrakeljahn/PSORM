<?php

namespace Entity;

use PS\Core\Database\Fields\StringField;
use PS\Core\Database\Entity;

class UserEntity extends Entity
{
    protected function setEntitname(): string
    {
        return 'User';
    }

    protected function setTabelName(): string
    {
        return 'users';
    }

    public function fieldDefinition(): array
    {
        $username = (new StringField('username'))
            ->setNotNullable(true)
            ->setRequired(true)
            ->setLength(255);
        $password = (new StringField('password'))
            ->setNotNullable(true)
            ->setRequired(true)
            ->setLength(255);
        $mail = (new StringField('mail'))
            ->setNotNullable(false)
            ->setLength(255);
        $firstName = (new StringField('firstName'))
            ->setNotNullable(false)
            ->setLength(255);
        $lastname = (new StringField('lastname'))
            ->setNotNullable(false)
            ->setLength(255);

        return [
            $username,
            $password,
            $mail,
            $firstName,
            $lastname,
        ];
    }
}
