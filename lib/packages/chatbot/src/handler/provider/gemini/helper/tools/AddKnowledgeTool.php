<?php

namespace PS\Package\Chatbot\Handler\Provider\Gemini\Helper\Tools;

use Config;
use ObjectPeer\KnowledgebitPeer;
use PS\Package\Chatbot\Handler\Provider\Gemini\GeminiHandler;

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
        $tableOfContent = "";
        $indexFilePath = Config::FILES_FOLDER . "knowledge/index.json";
        if (file_exists($indexFilePath)) {
            $tableOfContent = file_get_contents($indexFilePath);
        }

        $schema = json_encode([
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'title' => 'Directory Search',
            'description' => 'A tool for selecting relevant chapters from the knowledge base.',
            'properties' => [
                'data' => [
                    'type' => 'object',
                    'properties' => [
                        'dirs' => [
                            'type' => 'array',
                            'description' => 'An array of chapters that match the user query. For example ["dir1", "dir2/subdir1"]',
                        ],
                    ],
                    'required' => ['dirs'],
                    'additionalProperties' => false,
                ],
            ],
            'required' => ['data'],
            'additionalProperties' => false,
        ]);
        $arr = $chatInstance->getAiConversation();
        $arrContext = [...array_slice($arr, -2), [
            "role" => "user",
            "text" => $data['prompt']
        ]];
        $vecText = "";
        foreach ($arrContext as $context) {
            $vecText .= $context["text"];
        }
        $context = json_encode($arrContext);
        $prompt = "
            The user asked the following question: '{$data['prompt']}'.
            Here is the recent chat context: $context.
            The following chapters are available in the knowledge base: $tableOfContent.

            Please select the {$data['requestedBits']} chapters that best match the user's query.

            Your answer MUST follow this JSON Schema:
            $schema";

        $response = (new GeminiHandler())->generateContent($prompt)['message'] ?? '';
        $response = trim(str_replace(["```json", "```"], '', $response));

        $resData = json_decode($response, true)["data"]["dirs"];

        $arrKnowledgebit = KnowledgebitPeer::findMostRelevantBits(json_encode($vecText), $data['requestedBits'] < 3 ? 3 : $data['requestedBits'], $resData);
        foreach ($arrKnowledgebit as $knowledgebit) {
            $chatInstance->addKnowledgeBit($knowledgebit);
        }
    }
}
