<?php

namespace Oro\Bundle\MessageQueueBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverFactoryInterface;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * The message queue driver factory that wraps a drive created by a specific inner factory
 * with a driver that adds the current security token to a message.
 * @see \Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver
 */
class SecurityAwareDriverFactory implements DriverFactoryInterface
{
    /** @var DriverFactoryInterface */
    private $driverFactory;

    /** @var string[] */
    private $securityAgnosticTopics;

    /** @var TokenStorageInterface */
    private $tokenStorage;

    /** @var TokenSerializerInterface */
    private $tokenSerializer;

    /**
     * @param DriverFactoryInterface   $driverFactory
     * @param string[]                 $securityAgnosticTopics
     * @param TokenStorageInterface    $tokenStorage
     * @param TokenSerializerInterface $tokenSerializer
     */
    public function __construct(
        DriverFactoryInterface $driverFactory,
        array $securityAgnosticTopics,
        TokenStorageInterface $tokenStorage,
        TokenSerializerInterface $tokenSerializer
    ) {
        $this->driverFactory = $driverFactory;
        $this->securityAgnosticTopics = $securityAgnosticTopics;
        $this->tokenStorage = $tokenStorage;
        $this->tokenSerializer = $tokenSerializer;
    }

    /**
     * {@inheritdoc}
     */
    public function create(ConnectionInterface $connection, Config $config)
    {
        return new SecurityAwareDriver(
            $this->driverFactory->create($connection, $config),
            $this->securityAgnosticTopics,
            $this->tokenStorage,
            $this->tokenSerializer
        );
    }
}
