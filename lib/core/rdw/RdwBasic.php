<?php

namespace PS\Core\Rdw;

use DateTime;
use PS\Core\Api\Session;
use PS\Core\Database\DBConnector;

/**
 * Class RdwBasic
 *
 * Basic ActiveRecord-like class for RDW objects, including automatic persistence,
 * validation, and metadata handling (_createdAt, _createdBy, etc.).
 */
class RdwBasic
{
    /** @var DBConnector */
    private DBConnector $db;

    /** @var array|null Associative array of object properties */
    protected ?array $properties = [];

    /** @var array Settings like peerClass, isNew, etc. */
    protected array $settings = [
        'isNew' => false,
        'peerClass' => '',
        'wasNew' => false
    ];

    /**
     * Constructor.
     *
     * Initializes peerClass, properties, and DB connection.
     */
    public function __construct()
    {
        $this->settings['peerClass'] = "ObjectPeer\\" . explode("\\", get_called_class())[1] . "Peer";
        $this->initProperties();
        $this->db = new DBConnector();
    }

    /**
     * Initializes properties based on defined fields in the peer class.
     *
     * @return void
     */
    private function initProperties(): void
    {
        $fields = $this->settings['peerClass']::PROPERTIES;
        foreach ($fields as $field) {
            $this->properties[$field] = null;
        }
    }

    /**
     * Saves the current object to the database.
     * Inserts or updates depending on whether ID is set.
     *
     * @return $this
     * @throws \Exception
     */
    public final function save(): self
    {
        $user = null;
        $userID = null;

        try {
            $user = Session::getInstance()->getUser();
        } catch (\Exception) {
            $userID = null;
        }

        $date = new DateTime();
        $this->validateParameters();

        if ($this->properties['ID'] === null) {
            $this->settings['isNew'] = true;
            $this->properties['_createdAt'] = $date->format('Y-m-d H:i:s');
            $this->properties['_createdBy'] = $user?->getID() ?? $userID;
        } else {
            $this->properties['_modifiedAt'] = $date->format('Y-m-d H:i:s');
            $this->properties['_modifiedBy'] = $user?->getID() ?? $userID;
        }

        $this->storeToDatabase();
        return $this;
    }

    /**
     * Deletes the current object from the database.
     *
     * @return bool True on success, false on failure.
     */
    public final function delete(): bool
    {
        try {
            if ($this->properties['ID'] === null) {
                throw new \Exception("Cannot delete object that is not persisted.");
            }

            $sql = sprintf(
                "DELETE FROM `%s` WHERE ID = :ID",
                $this->settings['peerClass']::TABLE_NAME
            );

            $this->db->executeQuery($sql, ['ID' => $this->properties['ID']]);
            return true;
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Sets the object properties from an associative array.
     *
     * @param array $data
     * @return $this
     */
    public final function setPropertiesAsArray(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->properties[$key] = $value;
        }
        return $this;
    }

    /**
     * Returns whether the object is newly created (not yet persisted).
     *
     * @return bool
     */
    public final function getIsNew(): bool
    {
        return $this->settings['isNew'];
    }

    /**
     * Returns whether the object was created as new in the last `save()` call.
     *
     * @return bool
     */
    public final function getWasNew(): bool
    {
        return $this->settings['wasNew'];
    }

    /**
     * Validates all required properties before saving.
     *
     * @return void
     * @throws \Exception If any required property is null.
     */
    private function validateParameters(): void
    {
        $requiredFields = $this->settings['peerClass']::REQUIRED;

        foreach ($requiredFields as $field) {
            $getter = 'get' . ucfirst($field);
            if (method_exists($this, $getter)) {
                if (is_null($this->$getter())) {
                    throw new \Exception(sprintf("Property '%s' is required", $field));
                }
            } elseif (is_null($this->properties[$field] ?? null)) {
                throw new \Exception(sprintf("Property '%s' is required", $field));
            }
        }
    }

    /**
     * Inserts or updates the object in the database based on current state.
     *
     * @return void
     */
    private function storeToDatabase(): void
    {
        $properties = $this->settings['peerClass']::PROPERTIES;
        $_properties = array_filter($properties, fn($prop) => $prop !== 'ID');

        $_propertyData = [];
        foreach ($_properties as $property) {
            $_propertyData[$property] = $this->properties[$property];
        }

        if ($this->settings['isNew']) {
            $sql = sprintf(
                "INSERT INTO `%s` (%s) VALUES (:%s)",
                $this->settings['peerClass']::TABLE_NAME,
                implode(", ", $_properties),
                implode(", :", $_properties)
            );

            $pdo = $this->db->executeQuery($sql, $_propertyData, true);
            $this->properties['ID'] = (int) $pdo->lastInsertId();
            $this->settings['isNew'] = false;
            $this->settings['wasNew'] = true;
        } else {
            $_propertyData['ID'] = $this->properties['ID'];
            $sqlSet = implode(", ", array_map(fn($prop) => "`$prop` = :$prop", $_properties));

            $sql = sprintf(
                "UPDATE `%s` SET %s WHERE ID = :ID",
                $this->settings['peerClass']::TABLE_NAME,
                $sqlSet
            );

            $this->db->executeQuery($sql, $_propertyData, true);
        }
    }

    /**
     * Converts the object into an array.
     * Filters out non-API fields if $forApi is true.
     *
     * @param bool $forApi Whether to filter by API_READABLE.
     * @return array
     */
    public final function asArray(bool $forApi = false): array
    {
        $result = [];

        foreach ($this->properties as $key => $value) {
            if ($forApi && !in_array($key, $this->settings['peerClass']::API_READABLE)) {
                continue;
            }
            $result[$key] = $value;
        }

        return $result;
    }
}
