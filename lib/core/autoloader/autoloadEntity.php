<?php

spl_autoload_register(function ($class) {
    $base_dir = __DIR__ . '/../../';
    $arrPath = [
        ...glob($base_dir . 'core/src/_entities/*.php'),
        ...glob($base_dir . 'packages/*/_entities/*.php')
    ];

    foreach($arrPath as $path) {
        $normalizedPath = realpath($path);
        if ($normalizedPath === false) {
            continue;
        }
        $arrClassName = explode("\\", $class);
        if (isset($arrClassName[1]) && strpos($normalizedPath, $arrClassName[1] . '.php') !== false) {
            require $normalizedPath;
        }
    }
});
