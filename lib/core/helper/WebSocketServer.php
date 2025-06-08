<?php

namespace PS\Core\Helper;

use Config;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

class WebSocketServer
{
    private Worker $worker;

    public function __construct(string $host = '0.0.0.0', int $port = 2346)
    {
        $this->worker = new Worker("websocket://{$host}:{$port}");
        $this->worker::$logFile  = Config::LOG_FOLDER . 'websocket/websocket.log';
        $this->worker::$pidFile   = Config::LOG_FOLDER . 'websocket/websocket.pid';
        $this->worker::$statusFile   = Config::LOG_FOLDER . 'websocket/websocket.status';
        $this->worker->count = 1;

        $this->worker->onConnect = function (TcpConnection $connection) {
            echo "New connection: {$connection->id}\n";
        };

        $this->worker->onMessage = function (TcpConnection $connection, $data) {
            $connection->send($data);
        };

        $this->worker->onClose = function (TcpConnection $connection) {
            echo "Connection closed: {$connection->id}\n";
        };
    }

    public function setOnMessage(callable $callback): void
    {
        $this->worker->onMessage = $callback;
    }

    public function setOnConnect(callable $callback): void
    {
        $this->worker->onConnect = $callback;
    }

    public function setOnClose(callable $callback): void
    {
        $this->worker->onClose = $callback;
    }

    public function run(): void
    {
        Worker::runAll();
    }
}
