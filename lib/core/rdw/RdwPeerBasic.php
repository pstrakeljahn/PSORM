<?php

namespace PS\Core\Rdw;

use PS\Core\Database\Criteria;
use PS\Core\Database\DBConnector;

class RdwPeerBasic
{
    public static final function find(Criteria $criteria): void
    {

    }

    public static final function findById(int $id)
    {
        $callesClass = get_called_class();
        $tableName = $callesClass::TABLE_NAME;
        $sql = sprintf("SELECT * FROM %s  WHERE ID='%s'", $tableName, $id);
        $db = new DBConnector;
        $data = $db->executeQuery($sql);
        if(!count($data)) {
            return null;
        } else {
            $className = "Object\\" . str_replace("Peer", "", explode("\\", $callesClass)[1]);
            $instance = (new $className);
            $instance->setPropertiesAsArray($data[0]);
        }
        return $instance;
    }

}
