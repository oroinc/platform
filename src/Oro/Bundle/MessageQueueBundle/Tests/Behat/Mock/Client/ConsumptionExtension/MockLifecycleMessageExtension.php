<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Behat\Mock\Client\ConsumptionExtension;

use Doctrine\Common\Cache\Cache;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/**
 * Remove message from cached buffer when it processed
 */
class MockLifecycleMessageExtension extends AbstractExtension
{
    /** @var Config */
    private $config;

    /** @var Cache */
    private $cache;

    /**
     * @param Config $config
     * @param Cache $cache
     */
    public function __construct(Config $config, Cache $cache)
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

        $this->saveCache($message->getMessageId());
    }

    /**
     * @param string $messageId
     */
    private function saveCache($messageId)
    {
        $messages = $this->getConsumeMessages();
        $messages[$messageId] = true;
        $this->cache->save('consume_messages', serialize($messages));
    }

    /**
     * @return array
     */
    private function getConsumeMessages()
    {
        if (!$this->cache->contains('consume_messages')) {
            return [];
        }

        $messages = unserialize($this->cache->fetch('consume_messages'));
        if (!is_array($messages)) {
            return [];
        }

        return $messages;
    }
}
