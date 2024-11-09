<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use Config;
use PS\Core\Api\Abstracts\EndpointInterface;

class GetEndpoints extends BuildStep
{
    protected function setStepName(): string
    {
        return 'Fetching Custom Endpoints';
    }

    protected function setDescription(): string
    {
        return 'Load every mod-Endpoint from every package';
    }

    public function run(): bool
    {
        $base_dir = Config::BASE_PATH . 'lib/';
        $arrFiles = [
            ...glob($base_dir . 'core/src/api/resolver/*.php'),
            ...glob($base_dir . 'packages/*/api/resolver/*.php')
        ];

        $mappingEndpoints = [];

        foreach ($arrFiles as $file) {
            $arrEndpoints = require($file); {
                foreach ($arrEndpoints as $endpoint) {
                    if (in_array(EndpointInterface::class, class_implements($endpoint))) {
                        $definition = $endpoint::_define();
                        $mappingEndpoints[$definition->url] = [
                            'class' => $endpoint,
                        ];
                        foreach ($definition->allowedMethodes as $method) {
                            $mappingEndpoints[$definition->url][strtoupper($method)] = [];
                            if (in_array(strtoupper($method), array_keys($definition->requiredParamsByMethod))) {
                                $mappingEndpoints[$definition->url][strtoupper($method)] = $definition->requiredParamsByMethod[strtoupper($method)];
                            }
                        }
                    }
                }
            }
        }
        return file_put_contents(Config::BASE_PATH . 'build/customEndpoints/endpoints.php', "<?php\n\n return " . var_export($mappingEndpoints, true) . ";");
    }
}
