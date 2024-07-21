<?php

namespace PS\Core\_devtools\Helper;

use PS\Core\Database\Entity;
use PS\Core\Database\Fields\FieldBase;
use ReflectionClass;

class OptionRequestBuilder
{

    private const DATATYPE = "datatype";
    private const DATATYPE_TYPE = "type";
    private const DATATYPE_LENGTH = "length";
    private const DATATYPE_NULLABLE = "nullable";
    private const DATATYPE_FKSETTINGS = "fkSettings";
    private const OPTIONS = "options";
    private const OPTIONS_ENUM = "enum";
    private const RESTRICTIONS = "restrictions";
    private const RESTRICTIONS_REQUIRED = "required";

    private const BODY =
    [
        self::DATATYPE => [
            self::DATATYPE_TYPE => null,
            self::DATATYPE_LENGTH => null,
            self::DATATYPE_NULLABLE => null,
            self::DATATYPE_FKSETTINGS => null
        ],
        self::OPTIONS => [
            self::OPTIONS_ENUM => null
        ],
        self::RESTRICTIONS => [
            self::RESTRICTIONS_REQUIRED => null,
        ]
    ];

    public final static function getDataArray(Entity $instance): array
    {
        $returnArray = array();
        $fields = $instance->_getFields();
        foreach ($fields as $field) {
            if (!$field->apiReadable) continue;
            $tmp = self::BODY;

            $tmp[self::DATATYPE][self::DATATYPE_TYPE] = self::_get($field, 'datatype');
            $tmp[self::DATATYPE][self::DATATYPE_LENGTH] = self::_get($field, 'length');
            $tmp[self::DATATYPE][self::DATATYPE_NULLABLE] = !self::_get($field, 'notNullable');
            $tmp[self::DATATYPE][self::DATATYPE_FKSETTINGS] = self::_get($field, 'fkSettings');

            if (self::_get($field, 'datatype') === FieldBase::ENUM) {
                $tmp[self::OPTIONS][self::OPTIONS_ENUM] = self::_get($field, 'allowedValues');
            }

            $tmp[self::RESTRICTIONS][self::RESTRICTIONS_REQUIRED] = self::_get($field, 'required');

            $returnArray[self::_get($field, 'name')] = $tmp;
        }
        return $returnArray;
    }

    private static function _get($instance, $get)
    {
        $reflection = new ReflectionClass($instance);
        if ($reflection->hasProperty($get)) {
            $property = $reflection->getProperty($get);
            $property->setAccessible(true);
            return $property->getValue($instance);
        }
        return null;
    }
}
