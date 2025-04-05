<?php

namespace PS\Core\Helper;

use PS\Core\Api\Request;
use PS\Core\Database\Criteria;

class ApiHelper
{
    public const DEFAULT_PAGESIZE = 25;

    /**
     * Fetches an object or a paginated list of objects from the corresponding Peer class.
     *
     * @param string $objName   The name of the object/entity (PascalCase, no namespace).
     * @param mixed|null $id    Optional object ID to fetch a single record.
     * @param int|null $page    Optional page number (used only when $id is null).
     * @param int|null $pageSize Optional page size (used only when $id is null).
     *
     * @return array|null       Returns object data as array or list of arrays, or null if not found.
     * @throws \Exception       If entity is unavailable or data is invalid.
     */
    public static function findObject(string $objName, $id = null, int $page = null, int $pageSize = null): ?array
    {
        self::checkEntityAvailability($objName);

        $peerClass = "ObjectPeer\\{$objName}Peer";
        $result = [];

        if ($id !== null) {
            $instance = $peerClass::findById($id);
            $result = $instance?->asArray(true);
        } else {
            $criteria = self::buildCriteria($peerClass, $page, $pageSize);
            $instances = $peerClass::find($criteria);

            foreach ($instances as $instance) {
                $result[] = $instance->asArray(true);
            }
        }

        return $result;
    }

    /**
     * Saves an object by either updating an existing one or creating a new instance.
     *
     * @param string $objName  The name of the object/entity.
     * @param mixed|null $id   Optional ID for updating an existing object.
     *
     * @return array           The saved object's data as array.
     * @throws \Exception      If the entity is not available or the request is malformed.
     */
    public static function saveObject(string $objName, $id = null): array
    {
        self::checkEntityAvailability($objName);

        $request = Request::getInstance();
        $instance = null;

        if ($id !== null) {
            $peerClass = "ObjectPeer\\{$objName}Peer";
            $instance = $peerClass::findById($id);
            if (!$instance) {
                throw new \Exception("Object with ID {$id} not found.");
            }
        } else {
            $class = "Object\\{$objName}";
            if (!class_exists($class)) {
                throw new \Exception("Object class '{$class}' does not exist.");
            }
            $instance = new $class();
        }

        foreach ($request->parameters as $property => $value) {
            $setter = 'set' . ucfirst($property);
            if (method_exists($instance, $setter)) {
                $instance->$setter($value);
            } else {
                throw new \Exception("Unknown property '{$property}' for object '{$objName}'.");
            }
        }

        $instance->save();
        return $instance->asArray(true);
    }

    /**
     * Builds a Criteria object based on allowed parameters and pagination.
     *
     * @param string $peerClass The full class name of the Peer class.
     * @param int|null $page    Optional page number.
     * @param int|null $pageSize Optional page size.
     *
     * @return Criteria         The constructed Criteria object.
     * @throws \Exception       If disallowed parameters are used.
     */
    private static function buildCriteria(string $peerClass, ?int $page, ?int $pageSize): Criteria
    {
        $request = Request::getInstance();
        $criteria = Criteria::getInstace();

        foreach ($request->parameters as $key => $value) {
            if (!in_array($key, [...$peerClass::API_READABLE, '_page', '_pageSize'])) {
                throw new \Exception(sprintf("Property '%s' is not allowed", $key));
            }

            if (!in_array($key, ['_page', '_pageSize'])) {
                $criteria->add($key, $value, $value === 'null' ? Criteria::IS_NULL : '=');
            }
        }

        if ($page !== null && $pageSize !== -1) {
            $offset = ($page - 1) * $pageSize;
            $criteria->addLimit($offset, $pageSize);
        }

        return $criteria;
    }

    /**
     * Checks whether a given entity's Peer class is available and enabled.
     *
     * @param string $objName The name of the object/entity.
     *
     * @return void
     * @throws \Exception If the Peer class does not exist or is marked as disabled.
     */
    private static function checkEntityAvailability(string $objName): void
    {
        $peerClass = "ObjectPeer\\{$objName}Peer";

        if (!class_exists($peerClass) || !empty($peerClass::API_DISABLED)) {
            throw new \Exception("Entity '{$objName}' does not exist or is disabled.");
        }
    }
}
