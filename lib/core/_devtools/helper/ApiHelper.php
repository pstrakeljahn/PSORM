<?php

namespace PS\Core\_devtools\Helper;

use PS\Core\Api\Request;
use PS\Core\Database\Criteria;

class ApiHelper
{
    public static final function findObject(string $objName, $id = null): ?array
    {
        $returnArray = [];
        $peerClass = "ObjectPeer\\" . $objName . "Peer";
        if(!is_null($id)) {
            $instance = $peerClass::findById($id);    
            $returnArray = $instance?->asArray(true);
        } else {
            $criteria = self::buildCriteria($peerClass);
            $arrInstance = $peerClass::find($criteria);
            foreach($arrInstance as $instance) {
                $returnArray[] = $instance->asArray(true);
            }
        }
        return $returnArray;
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
        foreach($request->parameters as $property => $paramter) {
            $setter = 'set' . ucfirst($property);
            $instance->$setter($paramter);
        }
        $instance->save();
        return $instance->asArray(true);
    }

    private static function buildCriteria($peerClass): Criteria
    {
        $request = Request::getInstance();
        $criteria = Criteria::getInstace();
        if(!count($request->parameters)) {
            return $criteria;
        }
        foreach($request->parameters as $key => $value) {
            if(!in_array($key, $peerClass::API_READABLE)) {
                throw new \Exception(sprintf("Property '%s' is not allowed", $key));
            }
            $criteria->add($key, $value);
        }
        return $criteria;
    }
}
