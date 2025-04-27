<?php

namespace PS\Package\Chatbot\Handler\Provider\Gemini;

use Object\Knowledgebit;
use Object\User;
use PS\Package\Chatbot\Handler\Provider\AiChatSessionInterface;

class ChatSession implements AiChatSessionInterface
{
    private array $aiConversation = [];
    private array $conversation = [];
    private array $currentMessages = [];

    /** @var Knowledgebit[] $arrKnowledgeBit */
    private array $arrKnowledgeBit = [];

    private ?string $selectedModel = null;

    public function __construct(?string $initialContext = null)
    {
        $message = "You are a chatbot that is integrated into my web project. A user sends you a request. Additional information can come from the platform. These are marked with #ADDITIONAL CONTEXT: #. The user has not actively entered this and does not realize that you know this. But use this context to give a good answer. The system can also give you further instructions. This is flagged with #SYSTEM COMMAND: #. You can also get information about the user making the request. This information can be found in #USER INFORMATION: #.";

        $this->aiConversation[] = [
            'role' => 'user',
            'text' => $message
        ];

        if ($initialContext !== null) {
            $this->aiConversation[] = [
                'role' => 'user',
                'text' => "#ADDITIONAL CONTEXT: {$initialContext}#"
            ];
        }
    }

    public function setModel(string $model): self
    {
        if (!in_array($model, [
            GeminiHandler::GEMINI_1_5_FLASH,
            GeminiHandler::GEMINI_2_0_PRO,
            GeminiHandler::GEMINI_2_0_FLASH,
            GeminiHandler::GEMINI_2_0_FLASH_LITE,
            GeminiHandler::GEMINI_2_5_PRO,
            GeminiHandler::GEMINI_2_5_FLASH,
        ])) {
            throw new \Exception("Unknown model: " . $model);
        }
        $this->selectedModel = $model;
        return $this;
    }

    public function addSystemMessage(string $message): self
    {
        $this->aiConversation[] = [
            'role' => 'user',
            'text' => "#SYSTEM COMMAND: {$message}#"
        ];
        return $this;
    }

    private function addAiMessage(string $message)
    {
        $this->aiConversation[] = [
            'role' => 'assistant',
            'text' => $message
        ];
        $this->conversation[] = [
            'role' => 'assistant',
            'text' => $message
        ];
    }

    public function addUserMessage(string $message): self
    {
        $this->aiConversation[] = [
            'role' => 'user',
            'text' => $message
        ];
        $this->conversation[] = [
            'role' => 'user',
            'text' => $message
        ];
        return $this;
    }

    public function addUserContext(User $user): self
    {
        $userInformation = json_encode($user->asArray(true));
        $this->aiConversation[] = [
            'role' => 'user',
            'text' => "#USER INFORMATION: {$userInformation}#"
        ];
        return $this;
    }

    public function addKnowledgeBit(Knowledgebit $knowledgebit): self
    {
        if (!isset($this->arrKnowledgeBit[$knowledgebit->getID()])) {
            $this->arrKnowledgeBit[$knowledgebit->getID()] = $knowledgebit;
            $this->aiConversation[] = [
                'role' => 'user',
                'text' => "#CONTEXT INFORMATION: {$knowledgebit->getKnowledgejson()}#"
            ];
        }
        return $this;
    }

    /**
     * @param callable(string $chunk): void $callback
     * @throws \Exception
     */
    public function send(callable $callback)
    {
        $handler = new GeminiHandler($this->selectedModel ?? GeminiHandler::GEMINI_2_0_FLASH_LITE);
        $wrappedCallback = function ($chunk) use ($callback) {
            if (isset($chunk['message'])) {
                $this->currentMessages[] = $chunk['message'];
            }

            if (isset($chunk['inProgress']) && $chunk['inProgress'] === false) {
                $fullMessage = implode('', $this->currentMessages);
                $this->addAiMessage($fullMessage);
                $this->currentMessages = [];
            }
            $callback($chunk);
        };
        $postData = [
            "contents" => $this->buildPrompt()
        ];
        $handler->streamGenerateContentWithData($postData, $wrappedCallback);
    }

    public function buildPrompt(): array
    {
        $parts = [];
        foreach ($this->aiConversation as $msg) {
            $parts[] = [
                'role' => $msg['role'],
                'parts' => [
                    ['text' => $msg['text']]
                ]
            ];
        }
        return $parts;
    }
}
