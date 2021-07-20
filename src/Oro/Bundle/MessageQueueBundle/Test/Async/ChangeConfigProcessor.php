<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Async;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * Test config processor
 */
class ChangeConfigProcessor implements MessageProcessorInterface, TopicSubscriberInterface
{
    const TEST_TOPIC = 'oro.test.change_config';
    const COMMAND_NOOP = 'noop';
    const COMMAND_CHANGE_CACHE = 'change';

    /**
     * @var ConfigManager
     */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(MessageInterface $message, SessionInterface $session)
    {
        usleep(2000000); // Remove after BAP-16453 is fixed
        if ($message->getBody() !== self::COMMAND_NOOP) {
            $this->configManager->set('oro_locale.timezone', 'Europe/London');
            $this->configManager->flush();
        }

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return [self::TEST_TOPIC];
    }
}
