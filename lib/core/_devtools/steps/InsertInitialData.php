<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use Config;
use Object\User;
use ObjectPeer\UserPeer;
use PS\Core\Database\Criteria;

class InsertInitialData extends BuildStep
{
    protected function setStepName(): string
    {
        return 'Insert initial data';
    }

    protected function setDescription(): string
    {
        return 'Loads default data.';
    }

    public function run(): bool
    {
        if (defined('Config::ADMIN_USER')) {
            $arrUser = UserPeer::find(Criteria::getInstace()->add(UserPeer::USERNAME, Config::ADMIN_USER[UserPeer::USERNAME]));
            if (!count($arrUser)) {
                $user = (new User)
                    ->setUsername(Config::ADMIN_USER[UserPeer::USERNAME])
                    ->setPassword(Config::ADMIN_USER[UserPeer::PASSWORD]);
                if ($user->save()) {
                    echo "\t- " . sprintf("User '%s' created (PW: '%s')\n", Config::ADMIN_USER[UserPeer::USERNAME], Config::ADMIN_USER[UserPeer::PASSWORD]);
                }
            }
        }
        return true;
    }
}
