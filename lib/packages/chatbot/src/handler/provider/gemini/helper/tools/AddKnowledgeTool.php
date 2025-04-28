<?php

namespace PS\Package\Chatbot\Handler\Provider\Gemini\Helper\Tools;

use ObjectPeer\KnowledgebitPeer;

class AddKnowledgeTool implements AiToolInterface
{
    public static function getDescription(): string
    {
        return "This tool should be called up when the user asks context-related questions. Contextual information is then retrieved from the Knowledgebits database and is then available in the chat. Knowledgebits are very small units of information, usually just one sentence. “requestedBits” specifies the number of knowledgebits to be retrieved.";
    }

    public static function getJsonSchema(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'title' => 'AddKnowledge Tool',
            'description' => 'A tool for adding knowledge to the chat context.',
            'type' => 'object',
            'properties' => [
                'tool' => [
                    'type' => 'string',
                    'const' => 'addKnowledge'
                ],
                'data' => [
                    'type' => 'object',
                    'properties' => [
                        'requestedBits' => [
                            'type' => 'integer',
                            'description' => 'Number of knowledgebits to be loaded into the context.',
                        ],
                    ],
                    'required' => ['requestedBits'],
                    'additionalProperties' => false,
                ],
            ],
            'required' => ['tool', 'data'],
            'additionalProperties' => false,
        ];
    }

    public static function preExecute($data, &$chatInstance)
    {
        $arr = $chatInstance->getAiConversation();
        $knowledgePromt = [...array_slice($arr, -3), [
            "role" => "user",
            "text" => $data['prompt']
        ]];
        $arrKnowledgebit = KnowledgebitPeer::findMostRelevantBits(json_encode($knowledgePromt), $data['requestedBits']);
        foreach ($arrKnowledgebit as $knowledgebit) {
            $chatInstance->addKnowledgeBit($knowledgebit);
        }
    }
}
