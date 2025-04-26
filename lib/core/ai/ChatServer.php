<?php

namespace PS\Core\Ai;

use ObjectPeer\UserPeer;
use PS\Core\Api\Session;
use PS\Core\Helper\WebSocketServer;
use PS\Core\Logging\Logging;
use Workerman\Connection\TcpConnection;

class ChatServer
{
    private const INIT_COMMAND = "Initial context: You are a chatbot. Please do not use Markdown. You can use emojies to highlight things. From now on the input of the real user comes.";
    private WebSocketServer $server;
    private Logging $logInstance;
    private array $sessions = [];
    private array $connectionIDToUserID = [];

    public function __construct(string $host = '0.0.0.0', int $port = 2346)
    {
        $this->server = new WebSocketServer($host, $port);
        $this->logInstance = Logging::getInstance();

        $this->server->setOnConnect(function (TcpConnection $connection) {
            $this->handleConnect($connection);
        });

        $this->server->setOnMessage(function (TcpConnection $connection, $data) {
            $this->handleMessage($connection, $data);
        });

        $this->server->setOnClose(function (TcpConnection $connection) {
            $this->handleClose($connection);
        });
    }

    public function run(): void
    {
        $this->server->run();
    }

    private function handleConnect(TcpConnection $connection): void
    {
        $message = "New connection {$connection->id}: {$connection->getRemoteAddress()}";
        $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, $message, true);
    }

    private function handleMessage(TcpConnection $connection, $data): void
    {
        $data = json_decode($data, true);
        if (!isset($this->connectionIDToUserID[$connection->id])) {
            $user = UserPeer::findById($data["UserID"]);
            $sessionInstance = Session::getInstance(true);
            $loggedIn = $sessionInstance->getLoggedIn($data["jwt"]);
            if (!$loggedIn) {
                $message = "User (ID: {$user->getID()}) is not logged in!";
                $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, $message, true);
                $connection->send([
                    "message" => '',
                    "inProgress" => false
                ]);
            } else {
                $this->connectionIDToUserID[$connection->id] = $user->getID();
                $this->sessions[$connection->id . "_" . $this->connectionIDToUserID[$connection->id]] = new ChatSession($user, self::INIT_COMMAND);
            }
        }


        if (isset($this->connectionIDToUserID[$connection->id])) {
            $promt =  $data['message'];
            $message = "Received (UserID {$this->connectionIDToUserID[$connection->id]}): '{$promt}'";
            $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, $message, true);

            $session = $this->sessions[$connection->id . "_" . $this->connectionIDToUserID[$connection->id]];
            $gemini = new GeminiHandler();

            $session->addUserMessage($promt);
            $postData = [
                "contents" => $session->buildGeminiPrompt()
            ];

            try {
                $gemini->streamGenerateContentWithData($postData, function ($chunk) use ($connection, $session) {
                    $parsedChunk = $chunk['message'];
                    if ($parsedChunk !== null) {
                        $chunk['message'] = str_replace("\n", "<br />", $chunk['message']);
                        $session->addAssistantMessage($parsedChunk);
                        $connection->send($chunk);
                        $output = str_replace("<br />", "", $chunk['message']);
                        $message = "AI Response (UserID {$this->connectionIDToUserID[$connection->id]}): '{$output}'";
                        $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, $message, true);
                    }
                });
            } catch (\Exception $e) {
                $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, "ERROR OCCURED: " . $e->getMessage(), true);
            }

            $this->sessions[$connection->id . "_" . $this->connectionIDToUserID[$connection->id]] = $session;
        }
    }

    private function handleClose(TcpConnection $connection): void
    {
        $message = "Connection closed {$connection->id}: {$connection->getRemoteAddress()}";
        $this->logInstance->add(Logging::LOG_TYPE_CHATBOT, $message, true);
        if (isset($this->sessions[$connection->id . "_" . $this->connectionIDToUserID[$connection->id]])) {
            unset($this->sessions[$connection->id . "_" . $this->connectionIDToUserID[$connection->id]]);
        }
        if ($this->connectionIDToUserID[$connection->id]) {
            unset($this->connectionIDToUserID[$connection->id]);
        }
    }
}
