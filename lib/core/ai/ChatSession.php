<?php

namespace PS\Core\Ai;

use Object\User;

class ChatSession
{
    private array $messages = [];

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

    public function addUserMessage(string $message): void
    {
        $this->messages[] = [
            'role' => 'user',
            'text' => $message
        ];
    }

    public function addAssistantMessage(string $message): void
    {
        $this->messages[] = [
            'role' => 'assistant',
            'text' => $message
        ];
    }

    public function buildGeminiPrompt(): array
    {
        $parts = [];
        foreach ($this->messages as $msg) {
            $parts[] = [
                "role" => $msg['role'],
                "parts" => [
                    ["text" => $msg['text']]
                ]
            ];
        }
        return $parts;
    }
}
