<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment\Processor;

use Oro\Bundle\ApiBundle\Processor\UpdateList\UpdateListContext;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueConsumerTestTrait;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class WaitForSynchronousModeMessagesProcessed implements ProcessorInterface
{
    use MessageQueueAssertTrait;
    use MessageQueueConsumerTestTrait;

    private static ?ContainerInterface $container = null;

    public static function setContainer(?ContainerInterface $container): void
    {
        self::$container = $container;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var UpdateListContext $context */

        if (!$context->isSynchronousMode() || !$context->isProcessByMessageQueue()) {
            return;
        }

        $token = self::getTokenStorage()->getToken();
        try {
            self::consumeMessages();
        } finally {
            self::getTokenStorage()->setToken($token);
        }
    }

    private static function consumeMessages(): void
    {
        $sentMessagesCount = \count(self::getSentMessages());
        while ($sentMessagesCount > 0) {
            self::clearMessageCollector();
            self::consume($sentMessagesCount);
            self::clearProcessedMessages();
            $sentMessagesCount = \count(self::getSentMessages());
        }
    }

    private static function getTokenStorage(): TokenStorageInterface
    {
        return self::getContainer()->get('security.token_storage');
    }

    private static function getContainer(): ContainerInterface
    {
        return self::$container;
    }
}
