<?php

namespace PS\Package\Chatbot\Handler\Provider\Gemini\Helper\Tools;

class DoNothingTool implements AiToolInterface
{
    public static function getDescription(): string
    {
        return "The user requests something generic that does not require any context from the platform.";
    }

    public static function getJsonSchema(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'title' => 'DoNothing Tool',
            'description' => 'If no further context is required, this tool should be used.',
            'type' => 'object',
            'properties' => [
                'tool' => [
                    'type' => 'string',
                    'const' => 'doNothing',
                ],
                'data' => [
                    'type' => 'null',
                    'description' => 'Nothing to do.',
                ],
            ],
            'required' => ['tool', 'data'],
            'additionalProperties' => false,
        ];
    }

    public static function preExecute($data, &$chatInstance)
    {
        return;
    }
}
