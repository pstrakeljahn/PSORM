<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use Config;
use PS\Core\Api\Abstracts\EndpointInterface;

/**
 * Step to load all custom endpoint classes from resolver files
 * and generate a central endpoint mapping.
 */
final class GetEndpoints extends BuildStep
{
    /**
     * @return string
     */
    protected function setStepName(): string
    {
        return 'Fetching Custom Endpoints';
    }

    /**
     * @return string
     */
    protected function setDescription(): string
    {
        return 'Load every mod-Endpoint from every package';
    }

    /**
     * Scans resolver files and writes all endpoints to build/customEndpoints/endpoints.php.
     *
     * @return bool
     */
    public function run(): bool
    {
        $resolverFiles = array_merge(
            glob(Config::BASE_PATH . 'lib/core/src/api/resolver/*.php') ?: [],
            glob(Config::BASE_PATH . 'lib/packages/*/api/resolver/*.php') ?: []
        );

        /** @var array<string, array<string, mixed>> $endpointMap */
        $endpointMap = [];

        foreach ($resolverFiles as $file) {
            /** @var array<class-string<EndpointInterface>> $endpoints */
            $endpoints = require $file;

            foreach ($endpoints as $endpointClass) {
                if (!class_exists($endpointClass) || !in_array(EndpointInterface::class, class_implements($endpointClass), true)) {
                    continue;
                }

                $definition = $endpointClass::_define();
                $url = $definition->url;

                $endpointMap[$url] = ['class' => $endpointClass];

                foreach ($definition->allowedMethodes as $method) {
                    $methodUpper = strtoupper($method);
                    $endpointMap[$url][$methodUpper] = $definition->requiredParamsByMethod[$methodUpper] ?? [];
                }
            }
        }

        $outputPath = Config::BASE_PATH . 'build/customEndpoints/endpoints.php';
        $exported = "<?php\n\nreturn " . var_export($endpointMap, true) . ";\n";

        return file_put_contents($outputPath, $exported) !== false;
    }
}
