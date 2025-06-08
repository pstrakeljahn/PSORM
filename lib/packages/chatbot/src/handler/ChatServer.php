<?php

namespace PS\Package\Chatbot\Handler;

use ObjectPeer\UserPeer;
use PS\Core\Api\Authmethodes\BearerToken;
use PS\Core\Api\Response;
use PS\Core\Helper\CliOutputHelper;
use PS\Core\Helper\WebSocketServer;
use PS\Core\Helper\WebSocketServerResponse;
use PS\Core\Logging\Logging;
use PS\Package\Chatbot\Handler\Provider\Gemini\ChatSession;
use Workerman\Connection\TcpConnection;
use Exception;

class ChatServer
{
    private const INITIAL_CONTEXT = "You are a friendly chatbot. Your name is \"Gansi\". Use a few emojis to lighten the atmosphere, but not too many. Not every message has to have an emoji. Introduce yourself briefly and greet the user by their first name in your first message. Only use information that you really know, e.g. through the context. Don't make up any additional information. If in doubt, be honest and say that you don't know. Use the german language by default, unless the user requests a different language. You are a digitial emplyoee of the \"Grüne Gans\".";

    private WebSocketServer $server;
    private Logging $logger;
    /** @var ChatSession[] */
    private array $sessions = [];
    private array $userData = [];
    private WebSocketServerResponse $response;

    public function __construct(string $host = '0.0.0.0', int $port = 2346)
    {
        $this->server = new WebSocketServer($host, $port);
        $this->logger = new Logging();

        $this->server->setOnConnect(fn(TcpConnection $connection) => $this->handleConnect($connection));
        $this->server->setOnMessage(fn(TcpConnection $connection, string $data) => $this->handleMessage($connection, $data));
        $this->server->setOnClose(fn(TcpConnection $connection) => $this->handleClose($connection));
    }

    public function run(): void
    {
        $this->server->run();
    }

    private function handleConnect(TcpConnection $connection): void
    {
        $this->log("New connection {$connection->id}: {$connection->getRemoteAddress()}");
    }

    private function handleMessage(TcpConnection $connection, string $data): void
    {
        $this->response = new WebSocketServerResponse();

        try {
            $arrData = $this->validateMessage($connection, $data);
            $this->processRequest($connection, $arrData);
        } catch (Exception $e) {
            CliOutputHelper::output("→ {$connection->getRemoteAddress()} - [EXCEPTION]: " . $e->getMessage(), true);
            $this->response->setStatus(500)->setError($e->getMessage());
        }

        $connection->send($this->response->getResponse());
    }

    private function handleClose(TcpConnection $connection): void
    {
        $this->log("Connection closed {$connection->id}: {$connection->getRemoteAddress()}");
    }

    /**
     * @param TcpConnection $connection
     * @param string $data
     * @return array{token: string, command: string, data: mixed, type: string}
     */
    private function validateMessage(TcpConnection $connection, string $data): array
    {
        $arrData = json_decode($data, true);
        if (!is_array($arrData)) {
            throw new Exception("Invalid JSON format.");
        }

        foreach (['token', 'command', 'data'] as $field) {
            if (!isset($arrData[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        $this->userData = BearerToken::decodeToken($arrData['token'], true);
        return $arrData;
    }

    /**
     * @param TcpConnection $connection
     * @param array $arrData
     */
    private function processRequest(TcpConnection $connection, array $arrData): void
    {
        $userId = $this->userData['UserID'] ?? null;
        $isTokenExpired = ($this->userData['exp'] ?? 0) < time();

        switch ($arrData["command"]) {
            case 'CLEARSESSION':
                $this->log("← {$connection->getRemoteAddress()} - [CHATSESSION CLEARED - UserID $userId]");
                if (isset($this->sessions[$userId])) {
                    unset($this->sessions[$userId]);
                }
            case 'LOGIN':
                if ($isTokenExpired) {
                    $this->response->setStatus(Response::UNAUTHORIZED)->setError("Token expired!");
                    $this->log("→ {$connection->getRemoteAddress()} - [SEND - UserID $userId]: Token expired!");
                    return;
                }

                $user = UserPeer::findById($userId);
                if (!$user) {
                    throw new Exception("User not found!");
                }

                if (!isset($this->sessions[$userId])) {
                    $this->log("→ {$connection->getRemoteAddress()} - [SEND - UserID $userId]: ChatSession created");
                    $initContext = self::INITIAL_CONTEXT;
                    $session = AiManager::getAiChatInstance(AiManager::PROVIDER_GEMINI, $initContext);
                    $session->addUserContext($user);
                    $this->sessions[$userId] = $session;
                    $session->send(function ($chunk) use ($connection, $userId) {
                        $this->sendChunkResponse($connection, $chunk, $userId);
                    });
                }

                return;

            case 'GETSESSION':
                if (!isset($this->sessions[$userId])) {
                    $this->response->setError("Session not found")->setStatus(Response::UNAUTHORIZED);
                    return;
                }
                $conv = $this->sessions[$userId]->getConversation();
                $this->response->setData($conv);
                $this->log("← {$connection->getRemoteAddress()} - [CONVERSATION RESTORED - UserID $userId]");
                return;

            case 'USER':
                if (!isset($this->sessions[$userId])) {
                    $this->response->setError("Session not found. Please login first.")->setStatus(Response::UNAUTHORIZED);
                    $this->log("→ {$connection->getRemoteAddress()} - [ERROR - UserID $userId]: Session not found");
                    return;
                }

                if ($isTokenExpired) {
                    $this->response->setStatus(Response::UNAUTHORIZED)->setError("Token expired!");
                    $this->log("→ {$connection->getRemoteAddress()} - [SEND - UserID $userId]: Token expired!");
                    return;
                }

                $query = $arrData['data']['query'] ?? '';
                CliOutputHelper::output("→ {$connection->getRemoteAddress()} - [USER INPUT - UserID $userId]: '{$query}'");

                $session = $this->sessions[$userId];
                $session->addUserMessage($query);
                $session->send(fn($chunk) => $this->sendChunkResponse($connection, $chunk, $userId));
                return;
            default:
                $this->response->setError("Unknown command type: " . $arrData['command'])->setStatus(Response::BAD_REQUEST);
        }
    }

    private function sendChunkResponse(TcpConnection $connection, $chunk, int $userId): void
    {
        $response = (new WebSocketServerResponse())->setData($chunk);
        $connection->send($response->getResponse());
        $output = str_replace("\n", "", $chunk["message"] ?? '');
        CliOutputHelper::output("← {$connection->getRemoteAddress()} - [AI RESPONSE - UserID $userId]: '{$output}'");
    }

    private function log(string $message): void
    {
        $this->logger->add(Logging::LOG_TYPE_CHATBOT, $message, true);
    }
}
