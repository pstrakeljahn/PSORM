<?php

namespace PS\Package\Template\Api\Ressource;

use PS\Core\Api\Abstracts\EndpointDto;
use PS\Core\Api\Abstracts\EndpointInterface;

class Test implements EndpointInterface
{
    public static function _define(): EndpointDto
    {
        $dto = new EndpointDto;
        $dto->url = 'mod/testendpoint';
        $dto->allowedMethodes = ['GET'];
        $dto->needsAuthorization = false;
        $dto->requiredParamsByMethod = ['GET' => ["param1", "param2"]];
        return $dto;
    }

    public function get()
    {
        return array("asd" => 123);
    }
}
