<?php

namespace PS\Core\Helper;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigHelper
{
    private static ?Environment $twig = null;

    private static function getTwig(string $path): Environment
    {
        if (self::$twig === null) {
            $loader = new FilesystemLoader($path);
            self::$twig = new Environment($loader);
        }
        return self::$twig;
    }

    public static function renderTemplate(string $templatePath, array $data): string
    {
        $twig = self::getTwig(dirname($templatePath));
        $template = basename($templatePath);
        return $twig->render($template, $data);
    }
}
