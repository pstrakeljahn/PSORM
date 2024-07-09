<?php

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../../';
    $coreEntityPath = $baseDir . 'core/src/';
    $buildPath = $baseDir . '../build/';
    $packagesPath = $baseDir . 'packages/';

    $paths = [
        'ObjectPeer' => $coreEntityPath,
        'Object' => $coreEntityPath,
        'Basic' => $buildPath . 'basic/',
        'PeerBasic' => $buildPath . 'peerBasic/'
    ];

    // Helper function to search for a file in a directory and its subdirectories
    $searchInPackages = function ($baseDir, $class) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir, RecursiveDirectoryIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->getFilename() === $class . '.php') {
                return $file->getPathname();
            }
        }
        return false;
    };

    foreach ($paths as $key => $path) {
        if (str_contains($class, $key)) {
            $parts = explode("\\", $class);
            $filename = ($key === 'Basic' || $key === 'PeerBasic') ? $path . $class . '.php' : $path . $parts[1] . '.php';
            if (file_exists($filename)) {
                require $filename;
                return;
            }

            // If the file is not found in the coreEntityPath, search in packages
            if ($key === 'Object' || $key === 'ObjectPeer') {
                $filename = $searchInPackages($packagesPath, $parts[1]);
                if ($filename) {
                    require $filename;
                    return;
                }
            }
        }
    }
});
