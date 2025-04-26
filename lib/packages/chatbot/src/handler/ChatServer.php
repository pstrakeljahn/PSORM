<?php

namespace PS\Package\Chatbot\Handler;

use ObjectPeer\UserPeer;
use PS\Core\Api\Session;
use PS\Core\Helper\CliOutputHelper;
use PS\Core\Helper\WebSocketServer;
use PS\Core\Logging\Logging;
use Workerman\Connection\TcpConnection;

class ChatServer
{
    private const INIT_COMMAND = "Initial context: You are a chatbot. You can use emojis to highlight things. Introduce yourself.";
    private WebSocketServer $server;
    private Logging $logInstance;
    /** @var ChatSession[] $initalSessions  */
    private array $initalSessions = [];
    /** @var ChatSession[] $sessions  */
    private array $sessions = [];

    private array $connectionIDToUserID = [];

    /**
     * ChatServer constructor.
     * Initializes the WebSocket server and sets up connection event handlers.
     *
     * @param string $host
     * @param int $port
     */
    public function __construct(string $host = '0.0.0.0', int $port = 2346)
    {
        $this->server = new WebSocketServer($host, $port);
        $this->logInstance = new Logging();

        $this->server->setOnConnect(fn(TcpConnection $connection) => $this->handleConnect($connection));
        $this->server->setOnMessage(fn(TcpConnection $connection, $data) => $this->handleMessage($connection, $data));
        $this->server->setOnClose(fn(TcpConnection $connection) => $this->handleClose($connection));
    }

    /**
     * Starts the WebSocket server.
     */
    public function run(): void
    {
        $this->server->run();
    }

    /**
     * Handles a new WebSocket connection.
     *
     * @param TcpConnection $connection
     */
    private function handleConnect(TcpConnection $connection): void
    {
        $message = "New connection {$connection->id}: {$connection->getRemoteAddress()}";
        $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, $message, true);
        $this->initalSessions[$connection->id] = new ChatSession();
        $this->initalSessions[$connection->id]->addUserMessage(self::INIT_COMMAND);

        $gemini = new GeminiHandler();
        $session = $this->initalSessions[$connection->id];
        $postData = [
            "contents" => $session->buildGeminiPrompt()
        ];
        $gemini->streamGenerateContentWithData($postData, function ($chunk) use ($connection, $session) {
            $parsedChunk = $chunk['message'] ?? null;
            if ($parsedChunk !== null) {
                $session->addAssistantMessage($parsedChunk);
                $connection->send($chunk);
                $output = str_replace("\n", "", $parsedChunk);
                CliOutputHelper::output("AI init: '{$output}'");
            }
        });
    }

    /**
     * Handles incoming messages from a WebSocket client.
     *
     * @param TcpConnection $connection
     * @param mixed $data
     */
    private function handleMessage(TcpConnection $connection, $data): void
    {
        $data = json_decode($data, true);

        if (!is_array($data) || !isset($data["UserID"], $data["jwt"])) {
            $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, "Invalid data received.", true);
            $connection->send([
                "message" => '',
                "inProgress" => false,
                "error" => "notLoggedIn",
                "code" => 403
            ]);
            return;
        }

        if (!isset($this->connectionIDToUserID[$connection->id])) {
            $user = UserPeer::findById($data["UserID"]);

            if (!$user) {
                $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, "User not found (ID: {$data["UserID"]})", true);
                $connection->send([
                    "message" => '',
                    "inProgress" => false,
                    "error" => null,
                    "code" => 200
                ]);
                return;
            }

            $sessionInstance = Session::getInstance(true);
            $loggedIn = $sessionInstance->getLoggedIn($data["jwt"]);

            if (!$loggedIn) {
                $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, "User (ID: {$user->getID()}) is not logged in!", true);
                $connection->send([
                    "message" => '',
                    "inProgress" => false,
                    "error" => null,
                    "code" => 200
                ]);
                return;
            }

            $this->connectionIDToUserID[$connection->id] = $user->getID();
            $this->sessions[$this->getSessionKey($connection->id)] = $this->initalSessions[$connection->id];
        }

        if (isset($this->connectionIDToUserID[$connection->id])) {
            $prompt = $data['message'] ?? '';
            $message = "Received (UserID {$this->connectionIDToUserID[$connection->id]}): '{$prompt}'";
            CliOutputHelper::output("Received (UserID {$this->connectionIDToUserID[$connection->id]}): '{$prompt}'");

            $session = $this->sessions[$this->getSessionKey($connection->id)];
            $gemini = new GeminiHandler();

            $session->addUserMessage($prompt);

            $postData = [
                "contents" => $session->buildGeminiPrompt()
            ];

            try {
                $gemini->streamGenerateContentWithData($postData, function ($chunk) use ($connection, $session) {
                    $parsedChunk = $chunk['message'] ?? null;
                    if ($parsedChunk !== null) {
                        $session->addAssistantMessage($parsedChunk);
                        $connection->send($chunk);
                        $output = str_replace("\n", "", $parsedChunk);
                        CliOutputHelper::output("AI Response (UserID {$this->connectionIDToUserID[$connection->id]}): '{$output}'");
                    }
                });
            } catch (\Exception $e) {
                $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, "ERROR OCCURRED: " . $e->getMessage(), true);
            }

            $this->sessions[$this->getSessionKey($connection->id)] = $session;
        }
    }

    /**
     * Handles the closing of a WebSocket connection.
     *
     * @param TcpConnection $connection
     */
    private function handleClose(TcpConnection $connection): void
    {
        $message = "Connection closed {$connection->id}: {$connection->getRemoteAddress()}";
        $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, $message, true);

        $sessionKey = $this->getSessionKey($connection->id);

        if (isset($this->sessions[$sessionKey])) {
            unset($this->sessions[$sessionKey]);
        }

        if (isset($this->connectionIDToUserID[$connection->id])) {
            unset($this->connectionIDToUserID[$connection->id]);
        }
    }

    /**
     * Returns the session key based on connection ID and user ID.
     *
     * @param int $connectionId
     * @return string
     */
    private function getSessionKey(int $connectionId): string
    {
        return $connectionId . "_" . ($this->connectionIDToUserID[$connectionId] ?? '');
    }
}
