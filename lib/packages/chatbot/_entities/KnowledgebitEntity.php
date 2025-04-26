<?php

namespace Entity;

use PS\Core\Database\Fields\StringField;
use PS\Core\Database\Entity;
use PS\Core\Database\Fields\BooleanField;
use PS\Core\Database\Fields\DateField;
use PS\Core\Database\Fields\JsonField;
use PS\Core\Database\Fields\TextField;

class KnowledgebitEntity extends Entity
{
    protected function setEntityName(): string
    {
        return 'Knowledgebit';
    }

    protected function setTableName(): string
    {
        return 'chatbot_knowledgebit';
    }

    public function fieldDefinition(): array
    {
        $refID = (new StringField('refID'))
            ->setNotNullable(true)
            ->setRequired(true)
            ->setLength(255);

        $knowledge = (new TextField('knowledge'))
            ->setRequired(true);

        $embedding = (new JsonField('embedding'))
            ->setNotNullable(false);

        $lastEmbedding = (new DateField("lastEmbedding"))
            ->setWithTime(true)
            ->setNotNullable(false);

        $active = (new BooleanField('active'))
            ->setDefault(1)
            ->setNotNullable(true)
            ->setRequired(true);

        return [
            $refID,
            $knowledge,
            $embedding,
            $lastEmbedding,
            $active
        ];
    }

    protected function apiDisabled(): bool
    {
        return true;
    }
}
