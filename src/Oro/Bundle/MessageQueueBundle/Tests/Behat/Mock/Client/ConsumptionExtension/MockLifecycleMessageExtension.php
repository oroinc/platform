<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Behat\Mock\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Component\Cache\Adapter\PdoAdapter;

/**
 * Remove message from cached buffer when it processed
 */
class MockLifecycleMessageExtension extends AbstractExtension
{
    /** @var Config */
    private $config;

    /** @var PdoAdapter */
    private $cache;

    public function __construct(Config $config, PdoAdapter $cache)
    {
        $this->config = $config;
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
        $message = $context->getMessage();
        $routerProcessorName = $this->config->getRouterMessageProcessorName();
        if ($message->getProperty(Config::PARAMETER_PROCESSOR_NAME) === $routerProcessorName) {
            return;
        }

        $this->cache->delete($message->getMessageId());
    }
}
