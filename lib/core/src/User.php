<?php

namespace Object;

use ObjectPeer\UserPeer;
use UserBasic;

class User extends UserBasic
{
    /* Buissnesslogic can be implemented here */
    public function setPassword($password)
    {
        return parent::setPassword(UserPeer::hashPassword($password));
    }
}
