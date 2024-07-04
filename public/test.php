<?php

use Entity\UserEntity;
use PS\Core\_devtools\BuildInstance;
use PS\Core\Devtools\InstallComposerPackages;
use PS\Core\Helper\ComposerHelper;

require '../lib/core/init.php';

class Test
{
    public function run(){
        BuildInstance::run();
    }
}
(new Test)->run();