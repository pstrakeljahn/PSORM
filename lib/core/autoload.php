<?php

spl_autoload_register(function ($class) {
	if ($class === 'Config') {
		require __DIR__ . '/../../config/Config.php';
		return;
	}

	$prefix = 'PS\\';
	$base_dir = __DIR__ . '/../';

	$len = strlen($prefix);
	if (strncmp($prefix, $class, $len) !== 0) {
		return;
	}

	$class = str_replace('\\Source\\', '\\src\\', $class);
	$class_name = str_replace($prefix, '', $class);

	$arrPath = explode('\\', $class_name);
	$filename = array_pop($arrPath);
	$class_path = implode(DIRECTORY_SEPARATOR, $arrPath);

	$file = $base_dir . strtolower(str_replace('\\', '/', $class_path)) . DIRECTORY_SEPARATOR . $filename . '.php';

	if (file_exists($file)) {
		require $file;
	}
});
