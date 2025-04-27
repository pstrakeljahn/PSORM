<?php

namespace PS\Package\Chatbot\Handler\Provider;

use Object\Knowledgebit;
use Object\User;

interface AiChatSessionInterface
{
    public function addSystemMessage(string $message): self;
    public function addUserMessage(string $message): self;
    public function addUserContext(User $user): self;
    public function addKnowledgeBit(Knowledgebit $knowledgebit): self;
    public function setModel(string $model): self;
    public function send(callable $callback);
}
