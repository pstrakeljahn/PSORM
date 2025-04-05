<?php

namespace PS\Core\_devtools\Helper;

use PS\Core\Database\Entity;
use PS\Core\Database\Fields\FieldBase;
use ReflectionClass;

/**
 * Class OptionRequestBuilder
 *
 * Builds a structured metadata array from an Entity for use in dynamic forms or APIs.
 */
class OptionRequestBuilder
{
    private const DATATYPE = 'datatype';
    private const DATATYPE_TYPE = 'type';
    private const DATATYPE_LENGTH = 'length';
    private const DATATYPE_NULLABLE = 'nullable';
    private const DATATYPE_FKSETTINGS = 'fkSettings';

    private const OPTIONS = 'options';
    private const OPTIONS_ENUM = 'enum';

    private const RESTRICTIONS = 'restrictions';
    private const RESTRICTIONS_REQUIRED = 'required';

    /** @var array Default body structure for each field */
    private const BODY_TEMPLATE = [
        self::DATATYPE => [
            self::DATATYPE_TYPE => null,
            self::DATATYPE_LENGTH => null,
            self::DATATYPE_NULLABLE => null,
            self::DATATYPE_FKSETTINGS => null,
        ],
        self::OPTIONS => [
            self::OPTIONS_ENUM => null,
        ],
        self::RESTRICTIONS => [
            self::RESTRICTIONS_REQUIRED => null,
        ]
    ];

    /**
     * Builds a metadata array describing the given Entity's fields.
     *
     * @param Entity $instance
     * @return array<string, array> Associative array with field names as keys.
     */
    public static function getDataArray(Entity $instance): array
    {
        $result = [];
        $fields = $instance->_getFields();

        foreach ($fields as $field) {
            if (!$field->apiReadable) {
                continue;
            }

            $data = self::BODY_TEMPLATE;

            $data[self::DATATYPE][self::DATATYPE_TYPE]       = self::getPrivateProperty($field, 'datatype');
            $data[self::DATATYPE][self::DATATYPE_LENGTH]     = self::getPrivateProperty($field, 'length');
            $data[self::DATATYPE][self::DATATYPE_NULLABLE]   = !self::getPrivateProperty($field, 'notNullable');
            $data[self::DATATYPE][self::DATATYPE_FKSETTINGS] = self::getPrivateProperty($field, 'fkSettings');

            if (self::getPrivateProperty($field, 'datatype') === FieldBase::ENUM) {
                $data[self::OPTIONS][self::OPTIONS_ENUM] = self::getPrivateProperty($field, 'allowedValues');
            }

            $data[self::RESTRICTIONS][self::RESTRICTIONS_REQUIRED] = self::getPrivateProperty($field, 'required');

            $fieldName = self::getPrivateProperty($field, 'name');
            $result[$fieldName] = $data;
        }

        return $result;
    }

    /**
     * Accesses a private/protected property via reflection.
     *
     * @param object $instance
     * @param string $propertyName
     * @return mixed|null
     */
    private static function getPrivateProperty(object $instance, string $propertyName): mixed
    {
        $reflection = new ReflectionClass($instance);
        if ($reflection->hasProperty($propertyName)) {
            $property = $reflection->getProperty($propertyName);
            $property->setAccessible(true);
            return $property->getValue($instance);
        }

        return null;
    }
}
