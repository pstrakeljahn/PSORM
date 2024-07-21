<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use PS\Core\Database\Entity;
use PS\Core\Helper\TwigHelper;
use Config;
use PS\Core\_devtools\Helper\EntityHelper;
use PS\Core\_devtools\Helper\OptionRequestBuilder;
use ReflectionClass;

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
            $data = $this->buildDataArray($instance);
            $this->createClassFile($data);
            $this->createPeerClassFile($data);
            $this->createBasicClass($instance, $data);
            $this->createPeerBasicClass($instance, $data);
        }
        return true;
    }
    
    private function buildDataArray(Entity $instance): array
    {
        $arrFieldNames = [];
        $arrRequiredFields = [];
        $arrApiReadable = [];
        $arrFieldsToRemove = [...$instance->arrPrimaryKey, ...$instance->arrMetaFields];
        foreach ($instance->_getFields() as $field) {
            if ($field->apiReadable) {
                $arrApiReadable[] = $field->name;
            }
            if (in_array($field, $arrFieldsToRemove)) {
                continue;
            }
            $arrFieldNames[] = $field->name;
            if ($field->required) {
                $arrRequiredFields[] = $field->name;
            }
        }
        return [
            'className' => $instance->entityName,
            'fields' => $arrFieldNames,
            'requiredFields' => $arrRequiredFields,
            'tableName' => $instance->table,
            'readableFields' => $arrApiReadable,
            'primaryKey' => array_map(function($obj) {return $obj->name;}, $instance->arrPrimaryKey),
            'metaFields' => array_map(function($obj) {return $obj->name;}, $instance->arrMetaFields),
            'options' => var_export(OptionRequestBuilder::getDataArray($instance), true)
        ];
    }

    private function createClassFile($data)
    {
        $templatePath = Config::BASE_PATH . 'lib/core/_devtools/templates/Basic.twig';
        $classDefinition = TwigHelper::renderTemplate($templatePath, $data);
        file_put_contents(sprintf('%sbuild/basic/%sBasic.php', Config::BASE_PATH, $data['className']), $classDefinition);
    }

    private function createPeerClassFile($data)
    {
        $templatePath = Config::BASE_PATH . 'lib/core/_devtools/templates/PeerBasic.twig';
        $classDefinition = TwigHelper::renderTemplate($templatePath, $data);
        file_put_contents(sprintf('%sbuild/peerBasic/%sPeerBasic.php', Config::BASE_PATH, $data['className']), $classDefinition);
    }

    private function createBasicClass($instance, $data)
    {
        $reflection = new ReflectionClass($instance);
        $filePath = implode("/", array_slice(explode("/", $reflection->getFileName()), 0, -2)) . "/" . $instance->entityName . ".php";
        if (!file_exists($filePath)) {
            $templatePath = Config::BASE_PATH . 'lib/core/_devtools/templates/ClassTemplate.twig';
            $classDefinition = TwigHelper::renderTemplate($templatePath, $data);
            file_put_contents(sprintf($filePath, Config::BASE_PATH, $data['className']), $classDefinition);
        }
    }

    private function createPeerBasicClass($instance, $data)
    {
        $reflection = new ReflectionClass($instance);
        $filePath = implode("/", array_slice(explode("/", $reflection->getFileName()), 0, -2)) . "/" . $instance->entityName . "Peer.php";
        if (!file_exists($filePath)) {
            $templatePath = Config::BASE_PATH . 'lib/core/_devtools/templates/PeerClassTemplate.twig';
            $classDefinition = TwigHelper::renderTemplate($templatePath, $data);
            file_put_contents(sprintf($filePath, Config::BASE_PATH, $data['className']), $classDefinition);
        }
    }
}
