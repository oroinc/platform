<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Oro\Bundle\MessageQueueBundle\Tests\Functional\Environment\TestBufferedMessageProducer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class KernelTerminateHandler
{
    /** @var ContainerInterface */
    private $container;

    /** @var TokenInterface|null */
    private $securityToken;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function onBeforeTerminate()
    {
        $securityTokenStorage = $this->getSecurityTokenStorage();
        $this->securityToken = $securityTokenStorage->getToken();
        $securityTokenStorage->setToken(null);

        $messageProducer = $this->getMessageProducer();
        if (null !== $messageProducer) {
            $messageProducer->stopSendingOfMessages();
        }
    }

    public function onAfterTerminate()
    {
        if (null !== $this->securityToken) {
            $this->getSecurityTokenStorage()->setToken($this->securityToken);
            $this->securityToken = null;
        }

        $messageProducer = $this->getMessageProducer();
        if (null !== $messageProducer) {
            $messageProducer->restoreSendingOfMessages();
        }
    }

    /**
     * @return TokenStorageInterface
     */
    private function getSecurityTokenStorage(): TokenStorageInterface
    {
        return $this->container->get('security.token_storage');
    }

    /**
     * @return TestBufferedMessageProducer|null
     */
    private function getMessageProducer(): ?TestBufferedMessageProducer
    {
        $messageProducer = $this->container->get(
            'oro_message_queue.client.buffered_message_producer',
            ContainerInterface::NULL_ON_INVALID_REFERENCE
        );
        if (null !== $messageProducer && !$messageProducer instanceof TestBufferedMessageProducer) {
            $messageProducer = null;
        }

        return $messageProducer;
    }
}
