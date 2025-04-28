<?php

namespace PS\Package\Chatbot\Handler\Provider\Gemini\Helper;

use PS\Package\Chatbot\Handler\Provider\Gemini\ChatSession;
use PS\Package\Chatbot\Handler\Provider\Gemini\GeminiHandler;

class AiToolHelper
{
    /**
     * @var GeminiHandler
     */
    private GeminiHandler $handler;

    private $userInput = null;

    /**
     * @var array<string, string> Mapping from tool name to class name
     */
    private array $toolMapping = [];

    public function __construct()
    {
        $this->handler = new GeminiHandler();
    }

    /**
     * Determines which AI tool should be used based on user input.
     *
     * @param string $userInput The user's input
     * @param ChatSession $chatInstance 
     *
     * @throws \RuntimeException If the AI response is invalid
     */
    public function callAiTool(string $userInput, &$chatInstance): void
    {
        $this->userInput = $userInput;
        $tools = $this->loadAvailableTools();

        $prompt = "To understand the user's input, decide whether an AI tool of the platform should be called. Here is an overview of all tools:\n";

        foreach ($tools as $tool) {
            $prompt .= "* " . $tool::getDescription() . "\n";
            $schema = $tool::getJsonSchema();
            $this->toolMapping[$schema['properties']['tool']['const']] = $tool;
            $prompt .= "  * Schema: " . json_encode($schema) . "\n";
        }

        $prompt .= "\nIt is important that your answer must strictly comply with the JSON schema.\n";
        $prompt .= "The user's original message:\n";
        $prompt .= $userInput;

        $this->callAi($prompt, $chatInstance);
    }

    /**
     * Loads the available tools from the _tools.php file.
     *
     * @return array The loaded tool classes
     */
    private function loadAvailableTools(): array
    {
        $path = realpath(__DIR__ . '/../../../../../meta/AiTools.php');
        if (file_exists($path)) {
            return require $path;
        }

        return [];
    }

    /**
     * Sends a prompt to the AI and processes the response.
     *
     * @param string $prompt The prompt to send
     * @param ChatSession $chatInstance
     *
     * @throws \RuntimeException If the AI response is invalid or incomplete
     */
    private function callAi(string $prompt, &$chatInstance): void
    {
        $response = $this->handler->generateContent($prompt)['message'] ?? '';
        $response = trim(str_replace(["```json", "```"], '', $response));

        $data = json_decode($response, true);
        $data['data']['prompt'] = $this->userInput;

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Bad formatted response: ' . json_last_error_msg());
        }

        $toolClass = $this->toolMapping[$data['tool']];
        $toolClass::preExecute($data['data'], $chatInstance);
    }
}
