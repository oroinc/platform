<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Behat\Mock\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Component\Cache\Adapter\PdoAdapter;

/**
 * Remove message from cached buffer when it processed
 */
class MockLifecycleMessageExtension extends AbstractExtension
{
    private PdoAdapter $cache;

    public function __construct(PdoAdapter $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context): void
    {
        $message = $context->getMessage();
        $this->cache->delete($message->getMessageId());
    }
}
