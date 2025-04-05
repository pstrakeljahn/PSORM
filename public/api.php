<?php

use PS\Core\Api\Abstracts\Endpoint;
use PS\Core\Api\Request;
use PS\Core\Api\Response;
use PS\Core\Api\Session;
use PS\Core\Helper\ApiHelper;
use PS\Core\Logging\Logging;

require_once '../lib/core/init.php';

class ApiController
{
    /**
     * Handles the entire API request lifecycle, including routing, session validation,
     * and formatting the response.
     *
     * @return void
     */
    public function handle(): void
    {
        try {
            $request = Request::getInstance();
            $segmentType = $request->segments[$request->apiIndex + 2] ?? null;

            $sessionInstance = Session::getInstance($segmentType === Request::TYPE_LOGIN);
            $loggedIn = $sessionInstance->getLoggedIn();

            if (
                !$loggedIn &&
                !in_array($segmentType, [Request::TYPE_LOGIN, Request::TYPE_REFRESH]) &&
                $request->httpMethod !== 'OPTIONS'
            ) {
                throw new \Exception('Not logged in');
            }

            $data = null;
            $status = null;
            $error = null;
            $additionalMeta = [];

            if (!$segmentType) {
                throw new \Exception('Missing request type');
            }

            $data = $this->handleRequestType($segmentType, $request, $sessionInstance, $status, $additionalMeta);

            (new Response)
                ->setError($error)
                ->setData($data)
                ->setStatus($status ?? ($data === null ? Response::NOT_FOUND : Response::STATUS_OK))
                ->getResponse($additionalMeta);
        } catch (\Exception $e) {
            $this->handleException($e, $loggedIn);
        }
    }

    /**
     * Handles the routing based on request type segment.
     *
     * @param string $type
     * @param Request $request
     * @param Session $sessionInstance
     * @param string|null $status Reference for HTTP response status
     * @param array $meta Reference for additional metadata in the response
     *
     * @return mixed
     * @throws \Exception
     */
    private function handleRequestType(string $type, Request $request, Session $sessionInstance, ?string &$status, array &$meta): mixed
    {
        return match ($type) {
            Request::TYPE_OBJ     => $this->handleObjectRequest($request, $status, $meta),
            Request::TYPE_LOGIN   => $sessionInstance->login(),
            Request::TYPE_REFRESH => $sessionInstance->refresh(),
            Request::TYPE_LOGOUT  => $sessionInstance->logout(),
            Request::TYPE_MOD,
            Request::TYPE_CORE    => Endpoint::getEndpointData(),
            default               => throw new \Exception("Unknown request type: {$type}")
        };
    }

    /**
     * Handles all object-related requests like GET, POST, PATCH, and OPTIONS.
     *
     * @param Request $request
     * @param string|null $status
     * @param array $meta
     *
     * @return array|null
     * @throws \Exception
     */
    private function handleObjectRequest(Request $request, ?string &$status, array &$meta): ?array
    {
        $objectName = $request->segments[$request->apiIndex + 3] ?? null;
        $objectID = $request->segments[$request->apiIndex + 4] ?? null;

        if (!$objectName) {
            throw new \Exception('Missing object name');
        }

        return match ($request->httpMethod) {
            'GET'     => $this->handleObjectGet($objectName, $objectID, $request, $meta),
            'POST'    => $this->handleObjectPost($objectName, $status),
            'PATCH'   => $objectID ? ApiHelper::saveObject($objectName, $objectID) : null,
            'OPTIONS' => $this->handleObjectOptions($objectName, $status),
            default   => throw new \Exception('Unsupported HTTP method'),
        };
    }

    /**
     * Handles GET requests for objects, with support for pagination and single-object fetch.
     *
     * @param string $objectName
     * @param string|null $objectID
     * @param Request $request
     * @param array $meta
     *
     * @return array|null
     */
    private function handleObjectGet(string $objectName, ?string $objectID, Request $request, array &$meta): ?array
    {
        if ($objectID) {
            return ApiHelper::findObject($objectName, $objectID);
        }

        $pageSize = (int)($request->parameters['_pageSize'] ?? ApiHelper::DEFAULT_PAGESIZE);
        $page = (int)($request->parameters['_page'] ?? 1);
        if ($pageSize === -1) {
            $page = 1;
        }

        $meta['page'] = $page;
        $meta['pageSize'] = $pageSize;

        $data = ApiHelper::findObject($objectName, null, $page, $pageSize);
        $meta['totalCount'] = count($data);

        return $data;
    }

    /**
     * Handles POST requests for creating new objects.
     *
     * @param string $objectName
     * @param string $status
     *
     * @return array
     */
    private function handleObjectPost(string $objectName, string &$status): array
    {
        $status = (string)Response::CREATED;
        return ApiHelper::saveObject($objectName);
    }

    /**
     * Handles OPTIONS requests to return allowed operations for an object.
     *
     * @param string $objectName
     * @param string $status
     *
     * @return array
     */
    private function handleObjectOptions(string $objectName, string &$status): array
    {
        $peerClass = "ObjectPeer\\{$objectName}Peer";
        $status = (string)Response::STATUS_OK;
        return $peerClass::OPTIONS;
    }

    /**
     * Handles and logs exceptions, sending back a structured error response.
     *
     * @param \Throwable $e
     * @param bool $loggedIn
     *
     * @return void
     */
    private function handleException(\Throwable $e, bool $loggedIn): void
    {
        $log = new Logging;
        $log->add(Logging::LOG_TYPE_API, $e->getMessage());

        (new Response)
            ->setError($e->getMessage())
            ->setStatus($loggedIn ? Response::SERVER_ERROR : Response::UNAUTHORIZED)
            ->setDebug($loggedIn ? [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTrace()
            ] : [])
            ->getResponse();
    }
}

(new ApiController)->handle();
