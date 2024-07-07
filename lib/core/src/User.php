<?php

namespace Object;

use UserBasic;

class User extends UserBasic {
    /* Buissnesslogic can be implemented here */
    public function setPassword($password)
    {
        parent::setPassword(password_hash($password, PASSWORD_ARGON2_PROVIDER));
    }
}
