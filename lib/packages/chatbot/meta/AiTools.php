<?php

use PS\Package\Chatbot\Handler\Provider\Gemini\Helper\Tools\AddKnowledgeTool;
use PS\Package\Chatbot\Handler\Provider\Gemini\Helper\Tools\DoNothingTool;

return [
    DoNothingTool::class,
    AddKnowledgeTool::class,
];
