<?php

namespace PS\Core\_devtools\Helper;

use PS\Core\Api\Request;
use PS\Core\Database\Criteria;

class ApiHelper
{
    public const DEFAULT_PAGESIZE = 25;
    public static final function findObject(string $objName, $id = null, $page = null, $pageSize = null): ?array
    {
        self::checkEntityAvailability($objName);
        $returnArray = [];
        $peerClass = "ObjectPeer\\" . $objName . "Peer";
        if (!is_null($id)) {
            $instance = $peerClass::findById($id);
            $returnArray = $instance?->asArray(true);
        } else {
            $criteria = self::buildCriteria($peerClass, $page, $pageSize);
            $arrInstance = $peerClass::find($criteria);
            foreach ($arrInstance as $instance) {
                $returnArray[] = $instance->asArray(true);
            }
        }
        return $returnArray;
    }

    public static final function saveObject(string $objName, $id = null)
    {
        self::checkEntityAvailability($objName);
        $request = Request::getInstance();
        if (!is_null($id)) {
            $peerClass = "ObjectPeer\\" . $objName . "Peer";
            $instance = $peerClass::findById($id);
        } else {
            $class = "Object\\" . $objName;
            $instance = new $class;
        }
        foreach ($request->parameters as $property => $paramter) {
            $setter = 'set' . ucfirst($property);
            $instance->$setter($paramter);
        }
        $instance->save();
        return $instance->asArray(true);
    }

    private static function buildCriteria($peerClass, $page, $pageSize): Criteria
    {
        $request = Request::getInstance();
        $criteria = Criteria::getInstace();
        foreach ($request->parameters as $key => $value) {
            if (!in_array($key, [...$peerClass::API_READABLE, "_page", "_pageSize"])) {
                throw new \Exception(sprintf("Property '%s' is not allowed", $key));
            }
            if (!in_array($key, ["_pageSize", "_page"])) {
                $criteria->add($key, $value, $value === 'null' ? Criteria::IS_NULL : "=");
            }
        }

        if ($page !== null && $pageSize !== -1) {
            $criteria->addLimit((($page - 1) * $pageSize) * $pageSize, $pageSize);
        }

        return $criteria;
    }

    private static function checkEntityAvailability(string $objName): void
    {
        $peerClass = "ObjectPeer\\" . $objName . "Peer";
        if (!class_exists($peerClass) || $peerClass::API_DISABLED) {
            throw new \Exception("Entity '$objName' does not exist.");
        }
    }
}
