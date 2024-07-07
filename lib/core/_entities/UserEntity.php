<?php

namespace Entity;

use PS\Core\Database\Fields\EnumField;
use PS\Core\Database\Fields\IntegerField;
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
        $firstName = (new StringField('firstName'))
            ->setNotNullable(true)
            ->setLength(45);
        $age = (new IntegerField('age'))
            ->setUnsigned(true);
        $role = (new EnumField('role'))
            ->setValues(array('admin', 'user'));

        return [$firstName, $age, $role];
    }
}
