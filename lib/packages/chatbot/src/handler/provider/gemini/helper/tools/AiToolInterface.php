<?php

namespace PS\Package\Chatbot\Handler\Provider\Gemini\Helper\Tools;

interface AiToolInterface
{
    public static function getDescription(): string;
    public static function getJsonSchema(): array;
    public static function preExecute($data, &$chatInstance);
}
