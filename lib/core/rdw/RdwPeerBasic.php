<?php

namespace PS\Core\Rdw;

use PS\Core\Database\Criteria;
use PS\Core\Database\DBConnector;

/**
 * Class RdwPeerBasic
 *
 * Base class for peer objects to handle basic SELECT queries with dynamic instantiation.
 */
class RdwPeerBasic
{
    /**
     * Finds multiple records by criteria.
     *
     * @param Criteria $criteria Criteria object containing WHERE/ORDER/LIMIT conditions.
     * @param bool $asArray If true, returns arrays instead of object instances.
     *
     * @return array Array of results, either as objects or associative arrays.
     * @throws \Exception On database or instantiation errors.
     */
    public static function find(Criteria $criteria, bool $asArray = false): array
    {
        $calledClass = get_called_class();
        $sql = sprintf("SELECT * FROM `%s` %s", $calledClass::TABLE_NAME, $criteria->getConditions());

        $db = new DBConnector();
        $data = $db->executeQuery($sql);

        if (empty($data)) {
            return [];
        }

        if ($asArray) {
            return $data;
        }

        $results = [];
        $className = "Object\\" . str_replace("Peer", "", explode("\\", $calledClass)[1]);

        foreach ($data as $row) {
            $instance = new $className();
            $instance->setPropertiesAsArray($row);
            $results[] = $instance;
        }

        return $results;
    }

    /**
     * Finds a single record by primary key ID.
     *
     * @param int|string $id The ID to look for.
     * @param bool $asArray If true, returns result as an array instead of an object.
     *
     * @return RdwBasic|null Single result (array or object), or null if not found.
     * @throws \Exception On database or instantiation errors.
     */
    public static function findById(int|string $id, bool $asArray = false): ?RdwBasic
    {
        $calledClass = get_called_class();
        $sql = sprintf("SELECT * FROM `%s` WHERE ID = :id", $calledClass::TABLE_NAME);

        $db = new DBConnector();
        $data = $db->executeQuery($sql, ['id' => $id]);

        if (empty($data)) {
            return null;
        }

        if ($asArray) {
            return $data[0];
        }

        $className = "Object\\" . str_replace("Peer", "", explode("\\", $calledClass)[1]);
        $instance = new $className();
        $instance->setPropertiesAsArray($data[0]);

        return $instance;
    }
}
