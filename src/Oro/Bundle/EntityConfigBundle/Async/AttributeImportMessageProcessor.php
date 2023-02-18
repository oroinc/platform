<?php

namespace Oro\Bundle\EntityConfigBundle\Async;

use Oro\Bundle\EntityConfigBundle\Async\Topic\AttributeImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Import\ImportMessageProcessor;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Attribute import jobs one by one
 */
class AttributeImportMessageProcessor extends ImportMessageProcessor implements TopicSubscriberInterface
{
    protected MessageProducerInterface $producer;

    public function setMessageProducer(MessageProducerInterface $producer): void
    {
        $this->producer = $producer;
    }

    public static function getSubscribedTopics()
    {
        return [AttributeImportTopic::getName()];
    }

    public function process(MessageInterface $message, SessionInterface $session)
    {
        $messageBody = $message->getBody();
        $subJobs = $messageBody['subJobs'];

        $result = parent::process($message, $session);

        if ($nextJob = array_shift($subJobs)) {
            $nextJob['subJobs'] = $subJobs;
            $this->producer->send(AttributeImportTopic::getName(), $nextJob);
        }

        return $result;
    }
}
