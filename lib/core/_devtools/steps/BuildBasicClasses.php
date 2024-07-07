<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use PS\Core\Database\Entity;
use PS\Core\Helper\TwigHelper;
use Config;
use PS\Core\_devtools\Helper\EntityHelper;

class BuildBasicClasses extends BuildStep
{
    private array $entityClasses = [];

    protected function setStepName(): string
    {
        return 'Build Basic classes';
    }

    protected function setDescription(): string
    {
        return '';
    }

    public function run(): bool
    {
        $this->entityClasses = EntityHelper::loadEntityClasses();
        foreach ($this->entityClasses as $instance) {
            /** @var Entity $instance */
            $arrFieldNames = [];
            foreach ($instance->_getFields() as $field) {
                $arrFieldNames[] = $field->name;
            }
            $data = [
                'className' => $instance->entityName,
                'fields' => $arrFieldNames,
                'requiredFields' => [],
                'readableFields' => []
            ];
            $this->createClassFile($data);
            $this->createPeerClassFile($data);
        }
        return true;
    }

    private function createClassFile($data)
    {
        $templatePath = Config::BASE_PATH . 'lib/core/_devtools/templates/Basic.twig';
        $classDefinition = TwigHelper::renderTemplate($templatePath, $data);
        file_put_contents(sprintf('%sbuild/basic/%s.php', Config::BASE_PATH, $data['className']), $classDefinition);
    }

    private function createPeerClassFile($data)
    {
        $templatePath = Config::BASE_PATH . 'lib/core/_devtools/templates/PeerBasic.twig';
        $classDefinition = TwigHelper::renderTemplate($templatePath, $data);
        file_put_contents(sprintf('%sbuild/peerBasic/%sPeer.php', Config::BASE_PATH, $data['className']), $classDefinition);
    }
}
