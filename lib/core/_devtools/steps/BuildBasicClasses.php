<?php

namespace PS\Core\_devtools\Steps;

use Config;
use PS\Core\_devtools\Abstracts\BuildStep;
use PS\Core\Database\Entity;
use PS\Core\Helper\TwigHelper;

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
        $this->loadEntityClasses();
        foreach($this->entityClasses as $entityClass) {
            /** @var Entity $instance */
            $instance = new $entityClass;
            $arrFieldNames = [];
            foreach($instance->_getFields() as $field)
            {
                $arrFieldNames[] = $field->name;
            }
            $data = [
                'className' => $instance->entityName,
                'fields' => $arrFieldNames
            ];
            $this->createClassFile($data);
        }
        return true;
    }

    private function loadEntityClasses(): void
    {
        $coreEntityPath = Config::BASE_PATH . 'lib/core/_entities/';
        // $packageEntities = @todo
        foreach(glob($coreEntityPath . "*.php") as $file)
        {
            $classString = pathinfo($file)['filename'];
            $class = 'Entity\\' . $classString ;
            if (is_subclass_of($class, Entity::class)) {
                $this->entityClasses[] = $class;
            }
        }
    }

    private function createClassFile($data)
    {
        $templatePath = Config::BASE_PATH . "lib/core/_devtools/templates/Basic.twig";
        $classDefinition = TwigHelper::renderTemplate($templatePath, $data);
        file_put_contents(sprintf("%sbuild/basic/%s.php", Config::BASE_PATH, $data['className']), $classDefinition);
    }
}
