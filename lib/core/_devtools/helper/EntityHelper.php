<?php

namespace PS\Core\_devtools\Helper;

use Config;
use PS\Core\Database\Entity;

class EntityHelper
{
    public static final function loadEntityClasses(): array
    {
        $returnArray = [];
        $coreEntityPath = Config::BASE_PATH . 'lib/core/_entities/';
        // $packageEntities = @todo
        foreach (glob($coreEntityPath . '*.php') as $file) {
            $classString = pathinfo($file)['filename'];
            $class = 'Entity\\' . $classString;
            if (is_subclass_of($class, Entity::class)) {
                $instance = new $class;
                $returnArray[] = $instance;
            }
        }
        return $returnArray;
    }
}
