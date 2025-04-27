<?php

namespace PS\Package\Chatbot\Handler;

use PS\Package\Chatbot\Handler\Provider\Gemini\ChatSession;

class AiManager
{

    const PROVIDER_GEMINI = "gemini";

    public static function getAiChatInstance(string $provider, ?string $initalContext = null)
    {
        switch ($provider) {
            case self::PROVIDER_GEMINI:
                return new ChatSession($initalContext);
            default:
                return null;
        }
    }
}
