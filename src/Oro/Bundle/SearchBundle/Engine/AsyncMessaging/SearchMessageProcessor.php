<?php

namespace Oro\Bundle\SearchBundle\Engine\AsyncMessaging;

use Oro\Bundle\SearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Client\Config as MessageQueConfig;
use Oro\Component\MessageQueue\Util\JSON;

class SearchMessageProcessor implements MessageProcessorInterface
{
    /**
     * @var IndexerInterface $indexer
     */
    private $indexer;

    /**
     * @param IndexerInterface $indexer
     */
    public function __construct(IndexerInterface $indexer)
    {
        $this->indexer = $indexer;
    }

    /**
     * Dispatch the message to a indexer
     *
     * @param MessageInterface $message
     * @param SessionInterface $session
     * @return void
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        $data = JSON::decode($message->getBody());

        switch ($message->getProperty(MessageQueConfig::PARAMETER_TOPIC_NAME)) {
            case AsyncIndexer::TOPIC_SAVE:
                $this->indexer->save($data['entity'], $data['context']);

                break;

            case AsyncIndexer::TOPIC_DELETE:
                $this->indexer->delete($data['entity'], $data['context']);

                break;

            case AsyncIndexer::TOPIC_REINDEX:
                $this->indexer->reindex($data['class'], $data['context']);

                break;

            case AsyncIndexer::TOPIC_RESET_INDEX:
                $this->indexer->resetIndex($data['class'], $data['context']);

                break;
        }
    }
}
