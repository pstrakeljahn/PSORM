<?php

namespace Entity;

use PS\Core\Database\Entity;
use PS\Core\Database\Fields\DateField;
use PS\Core\Database\Fields\StringField;

class ServiceEntity extends Entity
{
    public bool $withoutMeta = true;

    protected function setEntitname(): string
    {
        return 'Service';
    }

    protected function setTabelName(): string
    {
        return 'services';
    }

    public function fieldDefinition(): array
    {
        $className = (new StringField('className'))
            ->setNotNullable(false)
            ->setLength(255);

        $startTime = (new DateField('startTime'))
            ->setNotNullable(false)
            ->setWithTime(true);

        return [
            $className,
            $startTime
        ];
    }
}
