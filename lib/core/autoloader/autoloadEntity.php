<?php

spl_autoload_register(function ($class) {
    $base_dir = __DIR__ . '/../../';
    $coreEntityPath = $base_dir . 'core/src/_entities/';

    // @todo Include packeges
    $className = str_replace('Entity\\', '', $class);
    $fileName = $coreEntityPath . $className . '.php';
    if (file_exists($coreEntityPath . $className . '.php')) {
        require $fileName;
    }
});
