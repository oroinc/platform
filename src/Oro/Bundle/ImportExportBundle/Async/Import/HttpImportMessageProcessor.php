<?php

namespace Oro\Bundle\ImportExportBundle\Async\Import;

use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Util\JSON;

class HttpImportMessageProcessor extends ImportMessageProcessor
{
    /**
     * {@inheritdoc}
     */
    protected function getNormalizeBody(MessageInterface $message)
    {
        $body = JSON::decode($message->getBody());

        if (!isset(
            $body['jobId'],
            $body['userId'],
            $body['processorAlias'],
            $body['fileName'],
            $body['jobName'],
            $body['process'],
            $body['originFileName']
        )
        ) {
            return null;
        }

        return array_replace_recursive(
            [
                'options' => []
            ],
            $body
        );
    }
}
