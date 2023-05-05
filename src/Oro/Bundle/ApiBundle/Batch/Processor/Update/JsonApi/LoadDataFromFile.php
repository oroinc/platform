<?php

namespace Oro\Bundle\ApiBundle\Batch\Processor\Update\JsonApi;

use Oro\Bundle\ApiBundle\Batch\JsonUtil;
use Oro\Bundle\ApiBundle\Batch\Processor\Update\LoadDataFromFile as BaseLoadDataFromFile;
use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\GaufretteBundle\FileManager;

/**
 * Loads JSON:API data from a chunk file.
 */
class LoadDataFromFile extends BaseLoadDataFromFile
{
    /**
     * {@inheritdoc}
     */
    protected function loadData(string $fileName, FileManager $fileManager): array
    {
        $sourceData = JsonUtil::decode($fileManager->getFileContent($fileName));

        $headerSectionData = null;
        if (\array_key_exists(JsonApiDoc::JSONAPI, $sourceData)) {
            $headerSectionData = [JsonApiDoc::JSONAPI => $sourceData[JsonApiDoc::JSONAPI]];
            unset($sourceData[JsonApiDoc::JSONAPI]);
        }

        $data = [];
        foreach ($sourceData[JsonApiDoc::DATA] as $itemData) {
            $item = $headerSectionData ?? [];
            $item[JsonApiDoc::DATA] = $itemData;
            $data[] = $item;
        }

        return $data;
    }
}
