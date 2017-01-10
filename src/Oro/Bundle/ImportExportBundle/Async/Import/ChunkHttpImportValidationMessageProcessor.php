<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Topics;

class ChunkHttpImportValidationMessageProcessor extends AbstractChunkImportMessageProcessor
{
    protected function processData(array $body)
    {
        $this->httpImportHandler->setImportingFileName($body['filePath']);

        return $this->httpImportHandler->handleImportValidation(
            $body['jobName'],
            $body['processorAlias'],
            $body['options']
        );
    }

    protected function getSummaryMessage(array $data)
    {
        return sprintf(
            'Import validation of the %s from %s is completed.
                 Success: %s.
                 Info: %s.
                 Errors: %s',
            $data['filePath'],
            $data['entityName'],
            $data['success'] ? 'true' : 'false',
            json_encode($data['counts']),
            json_encode($data['errors'])
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [Topics::IMPORT_HTTP_VALIDATION];
    }
}
