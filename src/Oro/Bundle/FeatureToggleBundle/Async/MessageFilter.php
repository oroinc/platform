<?php

namespace Oro\Bundle\FeatureToggleBundle\Async;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\MessageQueueBundle\Client\MessageBuffer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;

/**
 * Filter out configured MQ messages by topic if feature is disabled.
 */
class MessageFilter implements MessageFilterInterface
{
    /**
     * @var FeatureChecker
     */
    private $featureChecker;

    public function __construct(
        FeatureChecker $featureChecker
    ) {
        $this->featureChecker = $featureChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(MessageBuffer $buffer): void
    {
        foreach ($buffer->getTopics() as $topic) {
            if ($this->featureChecker->isResourceEnabled($topic, 'mq_topics')) {
                continue;
            }

            foreach ($buffer->getMessagesForTopic($topic) as $messageId => $message) {
                $buffer->removeMessage($messageId);
            }
        }
    }
}
