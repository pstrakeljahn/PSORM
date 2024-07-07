<?php

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../../';
    $coreEntityPath = $baseDir . 'core/src/';
    $buildPath = $baseDir . '../build/';

    $paths = [
        'ObjectPeer' => $coreEntityPath,
        'Object' => $coreEntityPath,
        'Basic' => $buildPath . 'basic/',
        'PeerBasic' => $buildPath . 'peerBasic/'
    ];

    foreach ($paths as $key => $path) {
        if (str_contains($class, $key)) {
            $parts = explode("\\", $class);
            $filename = ($key === 'Basic' || $key === 'PeerBasic') ? $path . $class . '.php' : $path . $parts[1] . '.php';
            if (file_exists($filename)) {
                require $filename;
                return;
            }
        }
    }
});
