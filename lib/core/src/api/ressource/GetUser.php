<?php

namespace PS\Core\Src\Api\Ressource;

use PS\Core\Api\Abstracts\EndpointDto;
use PS\Core\Api\Abstracts\EndpointInterface;
use PS\Core\Api\Session;

class GetUser implements EndpointInterface
{
    public static function _define(): EndpointDto
    {
        $dto = new EndpointDto;
        $dto->url = 'core/getUser';
        $dto->allowedMethodes = ['GET'];
        return $dto;
    }

    public function get()
    {
        $session = Session::getInstance();
        $user = $session->getUser();
        return array("user" => $user->asArray(true), "userPreferences" => []);
    }
}
