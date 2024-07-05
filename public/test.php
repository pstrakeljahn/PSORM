<?php

use PS\Core\_devtools\BuildInstance;

require '../lib/core/init.php';

class Test
{
    public function run()
    {
        BuildInstance::run();
    }
}

(new Test)->run();
