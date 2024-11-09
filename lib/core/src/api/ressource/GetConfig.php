<?php

namespace PS\Core\Src\Api\Ressource;

use Config;
use PS\Core\Api\Abstracts\EndpointDto;
use PS\Core\Api\Abstracts\EndpointInterface;

class GetConfig implements EndpointInterface
{
    public static function _define(): EndpointDto
    {
        $dto = new EndpointDto;
        $dto->url = 'core/getConfig';
        $dto->allowedMethodes = ['GET'];
        return $dto;
    }

    public function get()
    {
        return array("debug" => Config::DEBUG);
    }
}
