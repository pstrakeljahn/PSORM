<?php

namespace PS\Core\Api\Abstracts;

interface EndpointInterface
{
    public static function _define(): EndpointDto;
}
