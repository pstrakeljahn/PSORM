<?php

namespace PS\Core\Rdw;

use PS\Core\Database\Criteria;
use PS\Core\Database\DBConnector;

class RdwPeerBasic
{
    public static final function find(Criteria $criteria, bool $asArray = false): array
    {
        $calledClass = get_called_class();
        $sql = sprintf("SELECT * FROM `%s` %s", $calledClass::TABLE_NAME, $criteria->getConditions());
        $db = new DBConnector;
        $data = $db->executeQuery($sql);
        if (empty($data)) {
            return [];
        }
        $results = [];
        if (!$asArray) {
            $className = "Object\\" . str_replace("Peer", "", explode("\\", $calledClass)[1]);
            foreach ($data as $row) {
                $instance = new $className;
                $instance->setPropertiesAsArray($row);
                $results[] = $instance;
            }
            return $results;
        } else {
            return $data;
        }
    }

    public static final function findById($id, bool $asArray = false)
    {
        $callesClass = get_called_class();
        $tableName = $callesClass::TABLE_NAME;
        $sql = sprintf("SELECT * FROM %s  WHERE ID='%s'", $tableName, $id);
        $db = new DBConnector;
        $data = $db->executeQuery($sql);
        if (!count($data)) {
            return null;
        } else {
            if (!$asArray) {
                $className = "Object\\" . str_replace("Peer", "", explode("\\", $callesClass)[1]);
                $instance = (new $className);
                $instance->setPropertiesAsArray($data[0]);
                return $instance;
            } else {
                return $data[0];
            }
        }
    }
}
