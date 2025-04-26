<?php

namespace ObjectPeer;

use KnowledgebitPeerBasic;
use PS\Core\Database\Criteria;
use PS\Package\Chatbot\Handler\GeminiEmbeddingHandler;
use PS\Package\Chatbot\Helper\EmbeddingHelper;

class KnowledgebitPeer extends KnowledgebitPeerBasic
{
    /**
     * @param string $query
     * @param int $limit
     * @return Knowledgebit[]
     */
    public static function findMostRelevantBits(string $query, int $limit = 5): array
    {
        $embeddingHandler = new GeminiEmbeddingHandler();
        $queryVector = $embeddingHandler->embedText($query);

        if ($queryVector === null) {
            return [];
        }

        $arrKnowledgebits = self::find(
            Criteria::getInstance()
                ->add(self::ACTIVE, true)
        );

        $scoredBits = [];

        foreach ($arrKnowledgebits as $bit) {
            /** @var Knowledgebit $bit */
            $embedding = json_decode($bit->getEmbedding(), true);

            if (!is_array($embedding)) {
                continue;
            }

            $score = EmbeddingHelper::cosineSimilarity($queryVector, $embedding);
            $scoredBits[] = [
                'bit' => $bit,
                'score' => $score
            ];
        }

        usort($scoredBits, fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice(array_column($scoredBits, 'bit'), 0, $limit);
    }
}
