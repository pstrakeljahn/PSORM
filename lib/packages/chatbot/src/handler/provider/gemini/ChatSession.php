<?php

namespace PS\Package\Chatbot\Handler\Provider\Gemini;

use Object\Knowledgebit;
use Object\User;
use PS\Package\Chatbot\Handler\Provider\AiChatSessionInterface;
use PS\Package\Chatbot\Handler\Provider\Gemini\Helper\AiToolHelper;

/**
 * Class representing a chatbot session using the Gemini AI provider.
 */
class ChatSession implements AiChatSessionInterface
{
    /**
     * @var array List of AI conversation messages for the AI provider.
     */
    private array $aiConversation = [];

    /**
     * @var array List of user-visible conversation messages.
     */
    private array $conversation = [];

    /**
     * @var array List of current AI message chunks during streaming.
     */
    private array $currentMessages = [];

    /**
     * @var AiToolHelper Helper to detect and execute AI tools based on user input.
     */
    private AiToolHelper $aiToolHelper;

    /**
     * @var Knowledgebit[] List of knowledge bits provided as additional context.
     */
    private array $arrKnowledgeBit = [];

    /**
     * @var string|null The selected AI model.
     */
    private ?string $selectedModel = null;

    /**
     * Constructor.
     *
     * @param string|null $initialContext Optional initial context to include in the conversation.
     */
    public function __construct(?string $initialContext = null)
    {
        $this->aiToolHelper = new AiToolHelper();

        $message = "You are a chatbot integrated into my web project. A user sends you a request. 
Additional information can come from the platform, flagged with #ADDITIONAL CONTEXT: #. 
The user is unaware of this context, but you should use it to provide better answers. 
System instructions are flagged with #SYSTEM COMMAND: #. 
User information is flagged with #USER INFORMATION: #.
Never name the chapter of knowledgebits.";

        $this->aiConversation[] = [
            'role' => 'user',
            'text' => $message,
        ];

        if ($initialContext !== null) {
            $this->aiConversation[] = [
                'role' => 'user',
                'text' => "#ADDITIONAL CONTEXT: {$initialContext}#",
            ];
        }
    }

    /**
     * Sets the AI model to use.
     *
     * @param string $model The model identifier.
     * @return self
     * @throws \Exception If the model is unknown.
     */
    public function setModel(string $model): self
    {
        if (!in_array($model, [
            GeminiHandler::GEMINI_1_5_FLASH,
            GeminiHandler::GEMINI_2_0_PRO,
            GeminiHandler::GEMINI_2_0_FLASH,
            GeminiHandler::GEMINI_2_0_FLASH_LITE,
            GeminiHandler::GEMINI_2_5_PRO,
            GeminiHandler::GEMINI_2_5_FLASH,
        ], true)) {
            throw new \Exception("Unknown model: " . $model);
        }
        $this->selectedModel = $model;
        return $this;
    }

    /**
     * Adds a system message to the conversation.
     *
     * @param string $message The system message.
     * @return self
     */
    public function addSystemMessage(string $message): self
    {
        $this->aiConversation[] = [
            'role' => 'user',
            'text' => "#SYSTEM COMMAND: {$message}#",
        ];
        return $this;
    }

    /**
     * Adds an AI-generated message to the conversation.
     *
     * @param string $message The AI's message.
     * @return void
     */
    private function addAiMessage(string $message): void
    {
        $this->aiConversation[] = [
            'role' => 'assistant',
            'text' => $message,
        ];
        $this->conversation[] = [
            'role' => 'assistant',
            'text' => $message,
        ];
    }

    /**
     * Adds a user message to the conversation and processes potential AI tools.
     *
     * @param string $message The user's message.
     * @return self
     */
    public function addUserMessage(string $message): self
    {
        $this->aiToolHelper->callAiTool($message, $this);

        $this->aiConversation[] = [
            'role' => 'user',
            'text' => $message,
        ];
        $this->conversation[] = [
            'role' => 'user',
            'text' => $message,
        ];
        return $this;
    }

    /**
     * Adds user information to the conversation.
     *
     * @param User $user The user object.
     * @return self
     */
    public function addUserContext(User $user): self
    {
        $userInformation = json_encode($user->asArray(true));
        $this->aiConversation[] = [
            'role' => 'user',
            'text' => "#USER INFORMATION: {$userInformation}#",
        ];
        return $this;
    }

    /**
     * Adds a knowledge bit to the conversation context.
     *
     * @param Knowledgebit $knowledgebit The knowledge bit to add.
     * @return self
     */
    public function addKnowledgeBit(Knowledgebit $knowledgebit): self
    {
        if (!isset($this->arrKnowledgeBit[$knowledgebit->getID()])) {
            $this->arrKnowledgeBit[$knowledgebit->getID()] = $knowledgebit;
            $this->aiConversation[] = [
                'role' => 'user',
                'text' => "#CONTEXT INFORMATION: {$knowledgebit->getKnowledgejson()}#",
            ];
        }
        return $this;
    }

    /**
     * Sends the current conversation to the AI and processes streaming responses.
     *
     * @param callable(string $chunk): void $callback A callback function for streaming chunks.
     * @return void
     * @throws \Exception If the streaming fails.
     */
    public function send(callable $callback): void
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
            'contents' => $this->buildPrompt(),
        ];

        $handler->streamGenerateContentWithData($postData, $wrappedCallback);
    }

    /**
     * Builds the prompt payload for the AI.
     *
     * @return array The prompt payload.
     */
    public function buildPrompt(): array
    {
        $parts = [];

        foreach ($this->aiConversation as $msg) {
            $parts[] = [
                'role' => $msg['role'],
                'parts' => [
                    ['text' => $msg['text']],
                ],
            ];
        }

        return $parts;
    }

    /**
     * Returns the user-visible conversation history.
     *
     * @return array The conversation messages.
     */
    public function getConversation(): array
    {
        return $this->conversation;
    }

    /**
     * Returns the AI-visible conversation history.
     *
     * @return array The conversation messages.
     */
    public function getAiConversation(): array
    {
        return $this->aiConversation;
    }
}
