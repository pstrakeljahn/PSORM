<?php

spl_autoload_register(function ($class) {
    if ($class === 'Config') {
        require __DIR__ . '/../../../config/Config.php';
        return;
    }

    $prefix = 'PS\\';
    $base_dir = __DIR__ . '/../../';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $class_name = str_replace($prefix, '', $class);

    $arrPath = explode('\\', $class_name);
    $filename = array_pop($arrPath);
    $class_path = implode(DIRECTORY_SEPARATOR, $arrPath);

    $file = $base_dir . strtolower(str_replace('\\', '/', $class_path)) . DIRECTORY_SEPARATOR . $filename . '.php';

    if (file_exists($file)) {
        require $file;
        return;
    }

    if ($arrPath[0] === "Package") {
        $path = [];
        for ($i = 2; $i <= count($arrPath) - 1; $i++) {
            $path[] = $arrPath[$i];
        }
        if ($arrPath[1] === "Core") {
            $file = sprintf("%score/%s%s/%s.php", $base_dir, $arrPath[2] !== "Meta" ? "src/" : '', strtolower(implode("/", $path)), $filename);
        } else {
            $file = sprintf("%spackages/%s/%s%s/%s.php", $base_dir, strtolower($arrPath[1]), !in_array($arrPath[2], ["Meta", "Api"]) ? "src/" : '', strtolower(implode("/", $path)), $filename);
        }
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});
