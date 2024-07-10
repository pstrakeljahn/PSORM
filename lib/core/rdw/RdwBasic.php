<?php

namespace PS\Core\Rdw;

use PS\Core\Database\DBConnector;

class RdwBasic
{
    private DBConnector $db;
    protected $properties;
    protected $settings = [
        'isNew' => false,
        'peerClass' => null,
        'wasNew' => false
    ];

    public function __construct()
    {
        $this->settings['peerClass'] = "ObjectPeer\\" . explode("\\", get_called_class())[1] . "Peer";
        $this->initProperties();
        $this->db = new DBConnector;
    }

    private function initProperties()
    {
        $fields = $this->settings['peerClass']::PROPERTIES;
        foreach ($fields as $field) {
            $this->properties[$field] = null;
        }
    }

    public final function save()
    {
        $this->validateParameters();
        if ($this->properties['ID'] === null) {
            $this->settings['isNew'] = true;
        }
        $this->storeToDatabase();
        return $this;
    }

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
        } catch (\Exception $e) {
            return false;
        }
    }

    public final function setPropertiesAsArray(array $data)
    {
        foreach ($data as $key => $value) {
            $this->properties[$key] = $value;
        }
        return $this;
    }

    public final function getIsNew()
    {
        return $this->settings['isNew'];
    }

    public final function getWasNew()
    {
        return $this->settings['wasNew'];
    }

    private function validateParameters(): void
    {
        $requiredFields = $this->settings['peerClass']::REQUIRED;
        foreach ($requiredFields as $requiredField) {
            $getter = "get" . ucfirst($requiredField);
            if (is_null($this->$getter())) {
                throw new \Exception(sprintf("Property '%s' is required", $requiredField));
            }
        }
    }

    private function storeToDatabase()
    {
        $properties = $this->settings['peerClass']::PROPERTIES;
        $_properties = $properties;
        unset($_properties[0]);

        $_propertyData = [];
        foreach ($_properties as $property) {
            $_propertyData[$property] = $this->properties[$property];
        }

        $sqlFields = implode(", ", $_properties);

        if ($this->settings['isNew']) {
            $sql = sprintf(
                "INSERT INTO `%s` (%s) VALUES (:%s)",
                $this->settings['peerClass']::TABLE_NAME,
                $sqlFields,
                implode(", :", $_properties)
            );
            $pdo = $this->db->executeQuery($sql, $_propertyData, true);
            $this->properties['ID'] = (int) $pdo->lastInsertId();
            $this->settings['isNew'] = false;
            $this->settings['wasNew'] = true;
        } else {
            $_propertyData['ID'] = $this->properties['ID'];
            $sqlSet = implode(", ", array_map(function ($prop) {
                return "$prop = :$prop";
            }, $_properties));

            $sql = sprintf(
                "UPDATE `%s` SET %s WHERE ID = :ID",
                $this->settings['peerClass']::TABLE_NAME,
                $sqlSet
            );
            $pdo = $this->db->executeQuery($sql, $_propertyData, true);
        }
    }

    public final function asArray(bool $forApi = false): array
    {
        $returnArray = [];
        foreach ($this->properties as $key => $value) {
            if(!in_array($key, $this->settings['peerClass']::API_READABLE) && $forApi) {
                continue;
            }
            $returnArray[$key] = $value;
        }
        return $returnArray;
    }
}
