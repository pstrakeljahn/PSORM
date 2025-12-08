<?php

namespace ObjectPeer;

use UserPeerBasic;

class UserPeer extends UserPeerBasic {

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}
