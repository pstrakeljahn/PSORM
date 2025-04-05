<?php

namespace PS\Core\Helper;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Class TwigHelper
 *
 * Simplifies the rendering of Twig templates.
 */
class TwigHelper
{
    /** @var Environment|null Cached Twig environment instance */
    private static ?Environment $twig = null;

    /**
     * Returns a shared Twig environment instance for the given template base path.
     * Initializes the instance if not yet created.
     *
     * @param string $templateBasePath The path to the templates directory.
     * @return Environment The Twig environment instance.
     */
    private static function getTwig(string $templateBasePath): Environment
    {
        if (self::$twig === null) {
            $loader = new FilesystemLoader($templateBasePath);
            self::$twig = new Environment($loader);
        }

        return self::$twig;
    }

    /**
     * Renders a template with provided data.
     *
     * @param string $templatePath Full path to the template file.
     * @param array $data Associative array passed to the template.
     * @return string Rendered template as string.
     */
    public static function renderTemplate(string $templatePath, array $data): string
    {
        $templateDirectory = dirname($templatePath);
        $templateFile = basename($templatePath);

        $twig = self::getTwig($templateDirectory);
        return $twig->render($templateFile, $data);
    }
}
