<?php

namespace PS\Core\Database;

use PS\Core\Database\Fields\DateField;
use PS\Core\Database\Fields\IntegerField;

/**
 * Class Entity
 *
 * Abstract base class for database entities providing metadata, table creation, and field definitions.
 */
abstract class Entity
{
    /** @var string Name of the associated database table */
    public string $table;

    /** @var string Name of the entity (used for class logic) */
    public string $entityName;

    /** @var array Primary key field definitions */
    public array $arrPrimaryKey;

    /** @var bool Whether this entity is disabled for API access */
    public bool $apiDisabled;

    /** @var array|null Cached combined fields */
    private ?array $preparedFields = null;

    /** @var array User-defined field definitions */
    protected array $fields = [];

    /** @var string Default name of the primary key field */
    public static string $primaryKey = 'ID';

    /** @var bool If true, skips adding _createdAt/_modifiedAt metadata fields */
    public bool $withoutMeta = false;

    /** @var array Metadata fields like _createdAt, _createdBy, etc. */
    public array $arrMetaFields = [];

    /**
     * Entity constructor. Initializes the entity via `run()`.
     */
    public function __construct()
    {
        $this->run();
    }

    /**
     * Returns the full field definition for this entity.
     *
     * @return array Associative array of field objects.
     */
    abstract public function fieldDefinition(): array;

    /**
     * Should return the name of the entity (typically equals the class name).
     *
     * @return string
     */
    abstract protected function setEntityName(): string;

    /**
     * Should return the name of the database table.
     *
     * @return string
     */
    abstract protected function setTableName(): string;

    /**
     * Override this to disable API access to this entity.
     *
     * @return bool
     */
    protected function apiDisabled(): bool
    {
        return false;
    }

    protected function withoutMeta(): bool
    {
        return false;
    }

    /**
     * Internal setup of entity structure: names, fields, metadata, etc.
     *
     * @return void
     */
    public final function run(): void
    {
        $this->entityName = ucfirst($this->setEntityName());
        $this->fields = $this->fieldDefinition();
        $this->table = $this->setTableName();
        $this->apiDisabled = $this->apiDisabled();

        // Define primary key field
        $this->arrPrimaryKey = [
            (new IntegerField(static::$primaryKey))
                ->setLength(10)
                ->setRequired(true)
                ->setUnsigned(true)
                ->setAutoIncrement(true),
        ];

        // Define meta fields
        if (!$this->withoutMeta) {
            $this->arrMetaFields = [
                (new DateField("_createdAt"))->setWithTime(true)->setNotNullable(false),
                (new IntegerField("_createdBy"))->setLength(10)->setNotNullable(false)->setUnsigned(true)->setForeignKey('users', 'ID'),
                (new DateField("_modifiedAt"))->setWithTime(true)->setNotNullable(false),
                (new IntegerField("_modifiedBy"))->setLength(10)->setNotNullable(false)->setUnsigned(true)->setForeignKey('users', 'ID'),
            ];
        }
    }

    /**
     * Returns all fields, including primary key and meta fields (if enabled).
     *
     * @return array
     */
    public final function _getFields(): array
    {
        if ($this->preparedFields === null) {
            $this->preparedFields = [
                ...$this->arrPrimaryKey,
                ...$this->fields
            ];

            if (!$this->withoutMeta) {
                $this->preparedFields = [
                    ...$this->preparedFields,
                    ...$this->arrMetaFields
                ];
            }
        }

        return $this->preparedFields;
    }

    /**
     * Returns a single field definition by name if it exists.
     *
     * @param string $name
     * @return mixed|null
     */
    public final function _getField(string $name): mixed
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * Allows disabling of meta fields (_createdAt, _createdBy, etc.).
     *
     * @param bool $val
     * @return $this
     */
    public final function setWithoutMeta(): self
    {
        $this->withoutMeta = $this->withoutMeta();
        return $this;
    }

    /**
     * Generates SQL for creating the corresponding MySQL table.
     *
     * @return string
     */
    public final function getCreateTableSQL(): string
    {
        $fieldsSQL = [];

        foreach ($this->_getFields() as $field) {
            $fieldsSQL[] = $field->getMySQLDefinition();
        }

        $fieldsSQL[] = 'PRIMARY KEY(`' . static::$primaryKey . '`)';

        return sprintf(
            'CREATE TABLE IF NOT EXISTS `%s` (%s)',
            $this->table,
            implode(', ', $fieldsSQL)
        );
    }

    /**
     * Returns an array of SQL ALTER statements for setting up foreign key constraints.
     *
     * @return array
     */
    public final function getFKConstraints(): array
    {
        $constraints = [];

        foreach ($this->_getFields() as $field) {
            if (method_exists($field, 'getFKConstraint')) {
                $fk = $field->getFKConstraint();
                if ($fk !== null) {
                    $key = $field->getFKConstraint(true);
                    $sql = str_replace("###TABLENAME###", $this->table, sprintf("ALTER TABLE %s %s", $this->table, $fk));
                    $constraints[$key] = $sql;
                }
            }
        }

        return $constraints;
    }
}
