<?php

namespace PS\Core\Ai;

use Object\User;

/**
 * Manages the chat session between a user and the AI,
 * including storing conversation history and building prompts.
 */
class ChatSession
{
    /**
     * @var array Conversation history with roles and messages.
     */
    private array $messages = [];

    /**
     * ChatSession constructor.
     * Initializes the session with user information and an optional initial context.
     *
     * @param User $user
     * @param string|null $initialContext
     */
    public function __construct(User $user, ?string $initialContext = null)
    {
        $this->messages[] = [
            'role' => 'user',
            'text' => "This is the information about the user who is currently asking you: " . json_encode($user->asArray(true))
        ];

        if ($initialContext !== null) {
            $this->messages[] = [
                'role' => 'user',
                'text' => "Initial context: " . $initialContext
            ];
        }
    }

    /**
     * Adds a new user message to the session history.
     *
     * @param string $message
     */
    public function addUserMessage(string $message): void
    {
        $this->messages[] = [
            'role' => 'user',
            'text' => $message
        ];
    }

    /**
     * Adds a new assistant (AI) message to the session history.
     *
     * @param string $message
     */
    public function addAssistantMessage(string $message): void
    {
        $this->messages[] = [
            'role' => 'assistant',
            'text' => $message
        ];
    }

    /**
     * Builds and returns the formatted message history for the Gemini API.
     *
     * @return array
     */
    public function buildGeminiPrompt(): array
    {
        $parts = [];

        foreach ($this->messages as $msg) {
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
