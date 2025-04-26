<?php

namespace PS\Package\Chatbot\Helper;

use Config;
use Object\Knowledgebit;
use ObjectPeer\KnowledgebitPeer;
use PS\Core\Database\Criteria;
use PS\Core\Helper\CliOutputHelper;

class IngestKnowledgeHelper
{
    public static function ingestFromDirectory(): void
    {
        $dir = Config::FILES_FOLDER . "knowledge";
        self::checkKnowledgeDirectoryExists($dir);

        foreach (glob($dir . '/*.txt') as $filepath) {
            $filename = basename($filepath, '.txt');

            $arrBits = KnowledgebitPeer::find(
                Criteria::getInstance()
                    ->add(KnowledgebitPeer::REFID, $filename)
            );

            if (count($arrBits)) {
                $knowledgebit = $arrBits[0];
            } else {
                $knowledgebit = new Knowledgebit();
                $knowledgebit->setRefid($filename);
                $knowledgebit->setActive(true);
            }

            /** @var Knowledgebit $knowledgebit */
            $fileModifiedTime = filemtime($filepath);
            $bitCreatedTime = strtotime($knowledgebit->get_modifiedAt() ?? $knowledgebit->get_createdAt());

            if ($fileModifiedTime > $bitCreatedTime) {
                $content = file_get_contents($filepath);
                CliOutputHelper::output("Ingest knowldge: " . $filename);
                $knowledgebit->setKnowledge($content);
                $knowledgebit->save();
            }
        }
    }

    private static function checkKnowledgeDirectoryExists(string $dir): void
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}
