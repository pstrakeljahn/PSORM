<?php

namespace PS\Package\Gans\Meta;

use PS\Core\Abstracts\MetaDto;
use PS\Core\Abstracts\MetaInterface;

class Meta implements MetaInterface {

    public static function define(): MetaDto
    {
        $dto = new MetaDto;
        $dto->version = "0.1";
        $dto->packageName = "template";
        return $dto;
    }
}
