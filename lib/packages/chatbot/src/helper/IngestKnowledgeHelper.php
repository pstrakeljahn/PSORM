<?php

namespace PS\Package\Chatbot\Helper;

use Config;
use DateTime;
use Object\Knowledgebit;
use ObjectPeer\KnowledgebitPeer;
use PS\Core\Database\Criteria;
use PS\Core\Helper\CliOutputHelper;
use PS\Package\Chatbot\Handler\Provider\Gemini\GeminiEmbeddingHandler;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class IngestKnowledgeHelper
{

    public static function run()
    {
        self::ingestFromDirectory();
        self::vectorizeKnwledgeBits();
    }

    private static function ingestFromDirectory(): void
    {
        $dir = Config::FILES_FOLDER . "knowledge";
        self::checkKnowledgeDirectoryExists($dir);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isFile() && $fileInfo->getExtension() === 'txt') {
                $filepath = $fileInfo->getPathname();
                $relativePath = str_replace($dir . DIRECTORY_SEPARATOR, '', $filepath);
                $chapter = str_replace(".txt", "", $relativePath);

                $arrBits = KnowledgebitPeer::find(
                    Criteria::getInstance()
                        ->add(KnowledgebitPeer::REFID, self::getRefIDHash($chapter))
                );

                if (count($arrBits)) {
                    $knowledgebit = $arrBits[0];
                } else {
                    $knowledgebit = new Knowledgebit();
                    $knowledgebit
                        ->setRefid(self::getRefIDHash($chapter))
                        ->setChapter($chapter)
                        ->setActive(true);
                }

                /** @var Knowledgebit $knowledgebit */
                $fileModifiedTime = filemtime($filepath);
                $bitCreatedTime = strtotime($knowledgebit->get_modifiedAt() ?? $knowledgebit->get_createdAt() ?? 0);

                if ($fileModifiedTime > $bitCreatedTime) {
                    CliOutputHelper::output("Save to database: " . $chapter);
                    $arrData = ["chapter" => $chapter, "infomation" => file_get_contents($filepath)];
                    $knowledgebit->setKnowledgejson(json_encode($arrData, JSON_PRETTY_PRINT));
                    $knowledgebit->save();
                }
            }
        }
    }


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
                CliOutputHelper::output("Create Vector (refID: {$knowledgebit->getRefid()}): " . $knowledgebit->getChapter());
                $res = $handler->embedText($knowledgebit->getKnowledgejson());
                $knowledgebit->setVector(json_encode($res, JSON_PRETTY_PRINT))
                    ->setLastembedding((new DateTime())->format("Y-m-d H:i:s"))
                    ->save();
            }
        }
    }

    private static function getRefIDHash($chapter)
    {
        return hash('sha256', $chapter);
    }

    private static function checkKnowledgeDirectoryExists(string $dir): void
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}
