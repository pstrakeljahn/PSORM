<?php

namespace PS\Package\Chatbot\Handler;

use ObjectPeer\KnowledgebitPeer;
use ObjectPeer\UserPeer;
use PS\Core\Api\Authmethodes\BearerToken;
use PS\Core\Helper\CliOutputHelper;
use PS\Core\Helper\WebSocketServer;
use PS\Core\Logging\Logging;
use PS\Package\Chatbot\Handler\Provider\Gemini\ChatSession;
use Workerman\Connection\TcpConnection;

class ChatServer
{
    private WebSocketServer $server;
    private Logging $logInstance;
    /** @var ChatSession[] $sessions  */
    private array $sessions = [];

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
    }

    /**
     * Handles incoming messages from a WebSocket client.
     *
     * @param TcpConnection $connection
     * @param string $data
     */
    private function handleMessage(TcpConnection $connection, $data): void
    {
        /** @var array{ token: string, message: string, type: string } $arrData */
        $arrData = json_decode($data, true);
        if (!is_array($arrData) || !isset($arrData['type'], $arrData['token'])) {
            $connection->send(json_encode([
                "message" => '',
                "inProgress" => false,
                "error" => "Invalid request format",
                "code" => 400
            ]));
            $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, "→ {$connection->getRemoteAddress()} - [ERROR]: Invalid request format", true);
            return;
        }
        $arrUser = BearerToken::decodeToken($arrData['token'], true);
        switch ($arrData['type']) {
            case 'LOGIN':
                if ($arrUser['exp'] < time()) {
                    $connection->send([
                        "message" => '',
                        "inProgress" => false,
                        "error" => "Token expired!",
                        "code" => 401
                    ]);
                    $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, "→ {$connection->getRemoteAddress()} - [SEND - UserID {$arrUser['UserID']}]: Token expired!", true);
                } else {
                    $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, "→ {$connection->getRemoteAddress()} - [SEND - UserID {$arrUser['UserID']}]: ChatSession created", true);
                    $initContext = "You are a friendly chatbot. Use a few emojies to lighten up the atmosphere. Introduce yourself briefly and greet the user by their first name in your first message. Only use information that you really know, e.g. through the context. Don't make up any additional information. If in doubt, be honest and say that you don't know. Use the german language by default, unless the user requests a different language.";
                    $user = UserPeer::findById($arrUser['UserID']);
                    $session = AiManager::getAiChatInstance(AiManager::PROVIDER_GEMINI, $initContext);
                    $session->addUserContext($user);
                    $callback = function ($chunk) use ($connection, $arrUser) {
                        $connection->send([
                            ...$chunk,
                            "error" => null,
                            "code" => 200
                        ]);
                        $output = str_replace("\n", "", $chunk["message"]);
                        CliOutputHelper::output("← {$connection->getRemoteAddress()} - [AI RESPONSE - UserID {$arrUser['UserID']}]: '{$output}'");
                    };
                    $session->send($callback);
                    $this->sessions[$arrUser['UserID']] = $session;
                }
                return;
            case 'USER':
                if (!isset($this->sessions[$arrUser['UserID']])) {
                    $connection->send(json_encode([
                        "message" => '',
                        "inProgress" => false,
                        "error" => "Session not found. Please login first.",
                        "code" => 401
                    ]));
                    $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, "→ {$connection->getRemoteAddress()} - [ERROR - UserID {$arrUser['UserID']}]: Session not found", true);
                    return;
                }
                $session = $this->sessions[$arrUser['UserID']];
                $query = $arrData['message'] ?? '';
                CliOutputHelper::output("→ {$connection->getRemoteAddress()} - [USER INPUT - UserID {$arrUser['UserID']}]: '{$query}'");
                $arrKnowledgebit = KnowledgebitPeer::findMostRelevantBits($query, 3);
                foreach ($arrKnowledgebit as $knowledgebit) {
                    $session->addKnowledgeBit($knowledgebit);
                }
                $session->addUserMessage($query);
                $callback = function ($chunk) use ($connection, $arrUser) {
                    $connection->send([
                        ...$chunk,
                        "error" => null,
                        "code" => 200
                    ]);
                    $output = str_replace("\n", "", $chunk["message"]);
                    CliOutputHelper::output("← {$connection->getRemoteAddress()} - [AI RESPONSE - UserID {$arrUser['UserID']}]: '{$output}'");
                };
                $session->send($callback);
                return;
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
    }
}
