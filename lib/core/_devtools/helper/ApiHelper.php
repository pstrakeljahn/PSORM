<?php

namespace PS\Core\_devtools\Helper;

use PS\Core\Api\Request;
use PS\Core\Database\Criteria;

class ApiHelper
{
    public static final function findObject(string $objName, $id = null): ?array
    {
        $peerClass = "ObjectPeer\\" . $objName . "Peer";
        if(!is_null($id)) {
            return $peerClass::findById($id, true);    
        } else {
            return $peerClass::find(Criteria::getInstace(), true);
        }
    }

    public static final function saveObject(string $objName, $id = null)
    {
        $request = Request::getInstance();
        if(!is_null($id)) {
            $peerClass = "ObjectPeer\\" . $objName . "Peer";
            $instance = $peerClass::findById($id);
        } else {
            $class = "Object\\" . $objName;
            $instance = new $class;
        }
        $instance->setPropertiesAsArray($request->parameters);
        $instance->save();
        return $instance->asArray();
    }
}
