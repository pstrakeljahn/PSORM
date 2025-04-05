<?php

namespace PS\Core\_devtools\Helper;

use Config;
use PS\Core\Database\Entity;

/**
 * Class EntityHelper
 *
 * Loads all classes extending the base Entity class from predefined folders.
 */
class EntityHelper
{
    /**
     * Scans project directories and loads all classes that extend PS\Core\Database\Entity.
     *
     * @return Entity[] Array of loaded Entity instances.
     */
    public static function loadEntityClasses(): array
    {
        $entities = [];

        $paths = [
            ...glob(Config::BASE_PATH . 'lib/core/src/_entities/*.php'),
            ...glob(Config::BASE_PATH . 'lib/packages/*/_entities/*.php'),
        ];

        foreach ($paths as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            $fullyQualified = 'Entity\\' . $className;

            // Safely check if class exists and is a valid Entity
            if (class_exists($fullyQualified) && is_subclass_of($fullyQualified, Entity::class)) {
                $entities[] = new $fullyQualified();
            }
        }

        return $entities;
    }
}
