<?php

namespace PS\Package\Chatbot\Helper;

use DateTime;
use Object\Knowledgebit;
use ObjectPeer\KnowledgebitPeer;
use PS\Core\Database\Criteria;
use PS\Package\Chatbot\Handler\GeminiEmbeddingHandler;

class EmbeddingHelper
{
    public static function vectorizeKnwledgeBits()
    {
        $arrKnowledgebit = KnowledgebitPeer::find(
            Criteria::getInstance()
                ->add(KnowledgebitPeer::ACTIVE, true)
        );

        foreach ($arrKnowledgebit as $knowledgebit) {
            /** @var Knowledgebit $knowledgebit */
            $lastEmbedding = $knowledgebit->getLastembedding();
            $lastUpdated = strtotime($knowledgebit->get_modifiedAt() ?? $knowledgebit->get_createdAt());

            $needsEmbedding = false;

            if ($lastEmbedding === null) {
                $needsEmbedding = true;
            } else {
                $lastEmbeddingTime = strtotime($lastEmbedding);
                if ($lastEmbeddingTime < $lastUpdated) {
                    $needsEmbedding = true;
                }
            }

            if ($needsEmbedding) {
                $handler = new GeminiEmbeddingHandler();
                $res = $handler->embedText($knowledgebit->getKnowledge());
                $knowledgebit->setEmbedding(json_encode($res, JSON_PRETTY_PRINT))
                    ->setLastembedding((new DateTime())->format("Y-m-d H:i:s"))
                    ->save();
            }
        }
    }

    public final static function cosineSimilarity(array $vecA, array $vecB): float
    {
        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < min(count($vecA), count($vecB)); $i++) {
            $dotProduct += $vecA[$i] * $vecB[$i];
            $normA += $vecA[$i] ** 2;
            $normB += $vecB[$i] ** 2;
        }

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
