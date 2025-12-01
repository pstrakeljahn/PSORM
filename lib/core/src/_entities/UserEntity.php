<?php

namespace Entity;

use PS\Core\Database\Fields\StringField;
use PS\Core\Database\Entity;
use PS\Core\Database\Fields\BooleanField;

class UserEntity extends Entity
{
    protected function setEntityName(): string
    {
        return 'User';
    }

    protected function setTableName(): string
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
            ->setApiReadable(false)
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
        $headerAuth = (new BooleanField('allowHeaderAuth'))
            ->setNotNullable(true)
            ->setDefault(false);

        return [
            $username,
            $password,
            $mail,
            $firstName,
            $lastname,
            $headerAuth
        ];
    }
}
