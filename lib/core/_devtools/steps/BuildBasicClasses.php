<?php

namespace PS\Core\_devtools\Steps;

use PS\Core\_devtools\Abstracts\BuildStep;
use PS\Core\Database\Entity;
use PS\Core\Helper\TwigHelper;
use Config;
use PS\Core\_devtools\Helper\EntityHelper;
use PS\Core\_devtools\Helper\OptionRequestBuilder;
use ReflectionClass;

/**
 * Step that generates basic object and peer class files from entity definitions.
 */
final class BuildBasicClasses extends BuildStep
{
    /** @var array<int, Entity> */
    private array $entityClasses = [];

    protected function setStepName(): string
    {
        return 'Build Basic classes';
    }

    protected function setDescription(): string
    {
        return 'Generates basic object and peer class templates for all detected entities.';
    }

    public function run(): bool
    {
        $this->entityClasses = EntityHelper::loadEntityClasses();

        foreach ($this->entityClasses as $instance) {
            $data = $this->buildDataArray($instance);

            $this->createClassFile($data);
            $this->createPeerClassFile($data);
            $this->createBasicClass($instance, $data);
            $this->createPeerBasicClass($instance, $data);
        }

        return true;
    }

    /**
     * Builds the context array for the Twig templates.
     *
     * @param Entity $instance
     * @return array<string, mixed>
     */
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

            if (in_array($field, $arrFieldsToRemove, true)) {
                continue;
            }

            $arrFieldNames[] = $field->name;

            if ($field->required) {
                $arrRequiredFields[] = $field->name;
            }
        }

        return [
            'className'       => $instance->entityName,
            'fields'          => $arrFieldNames,
            'requiredFields'  => $arrRequiredFields,
            'tableName'       => $instance->table,
            'readableFields'  => $arrApiReadable,
            'apiDisabled'     => $instance->apiDisabled,
            'primaryKey'      => array_map(fn($obj) => $obj->name, $instance->arrPrimaryKey),
            'metaFields'      => array_map(fn($obj) => $obj->name, $instance->arrMetaFields),
            'options'         => var_export(OptionRequestBuilder::getDataArray($instance), true)
        ];
    }

    /**
     * Generates the basic class file from template.
     *
     * @param array<string, mixed> $data
     */
    private function createClassFile(array $data): void
    {
        $templatePath = Config::BASE_PATH . 'lib/core/_devtools/templates/Basic.twig';
        $output = TwigHelper::renderTemplate($templatePath, $data);

        file_put_contents(
            sprintf('%sbuild/basic/%sBasic.php', Config::BASE_PATH, $data['className']),
            $output
        );
    }

    /**
     * Generates the peer class file from template.
     *
     * @param array<string, mixed> $data
     */
    private function createPeerClassFile(array $data): void
    {
        $templatePath = Config::BASE_PATH . 'lib/core/_devtools/templates/PeerBasic.twig';
        $output = TwigHelper::renderTemplate($templatePath, $data);

        file_put_contents(
            sprintf('%sbuild/peerBasic/%sPeerBasic.php', Config::BASE_PATH, $data['className']),
            $output
        );
    }

    /**
     * Creates the actual class file if not already present.
     *
     * @param Entity $instance
     * @param array<string, mixed> $data
     */
    private function createBasicClass(Entity $instance, array $data): void
    {
        $reflection = new ReflectionClass($instance);
        $dir = dirname(dirname($reflection->getFileName()));
        $filePath = $dir . DIRECTORY_SEPARATOR . $instance->entityName . '.php';

        if (!file_exists($filePath)) {
            $templatePath = Config::BASE_PATH . 'lib/core/_devtools/templates/ClassTemplate.twig';
            $output = TwigHelper::renderTemplate($templatePath, $data);
            file_put_contents($filePath, $output);
        }
    }

    /**
     * Creates the actual peer class file if not already present.
     *
     * @param Entity $instance
     * @param array<string, mixed> $data
     */
    private function createPeerBasicClass(Entity $instance, array $data): void
    {
        $reflection = new ReflectionClass($instance);
        $dir = dirname(dirname($reflection->getFileName()));
        $filePath = $dir . DIRECTORY_SEPARATOR . $instance->entityName . 'Peer.php';

        if (!file_exists($filePath)) {
            $templatePath = Config::BASE_PATH . 'lib/core/_devtools/templates/PeerClassTemplate.twig';
            $output = TwigHelper::renderTemplate($templatePath, $data);
            file_put_contents($filePath, $output);
        }
    }
}
