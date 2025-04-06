<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use Config;
use Object\User;
use ObjectPeer\UserPeer;
use PS\Core\Database\Criteria;

/**
 * Step that inserts default or initial data into the system.
 */
final class InsertInitialData extends BuildStep
{
    /**
     * @return string
     */
    protected function setStepName(): string
    {
        return 'Insert initial data';
    }

    /**
     * @return string
     */
    protected function setDescription(): string
    {
        return 'Loads default data (e.g. admin user).';
    }

    /**
     * Executes the data insertions.
     *
     * @return bool True if step runs successfully
     */
    public function run(): bool
    {
        if (defined('Config::ADMIN_USER')) {
            $arrUser = UserPeer::find(Criteria::getInstance()->add(UserPeer::USERNAME, Config::ADMIN_USER[UserPeer::USERNAME]));
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
