<?php

namespace Oro\Bundle\MessageQueueBundle\Security;

use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverFactoryInterface;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;

/**
 * The message queue driver factory that wraps a drive created by a specific inner factory
 * with a driver that adds the current security token to a message.
 * @see \Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver
 */
class SecurityAwareDriverFactory implements DriverFactoryInterface
{
    private DriverFactoryInterface $driverFactory;
    /** @var string[] */
    private array $securityAgnosticTopics;
    private SecurityTokenProviderInterface $tokenProvider;
    private TokenSerializerInterface $tokenSerializer;

    public function __construct(
        DriverFactoryInterface $driverFactory,
        array $securityAgnosticTopics,
        SecurityTokenProviderInterface $tokenProvider,
        TokenSerializerInterface $tokenSerializer
    ) {
        $this->driverFactory = $driverFactory;
        $this->securityAgnosticTopics = $securityAgnosticTopics;
        $this->tokenProvider = $tokenProvider;
        $this->tokenSerializer = $tokenSerializer;
    }

    /**
     * {@inheritDoc}
     */
    public function create(ConnectionInterface $connection, Config $config)
    {
        return new SecurityAwareDriver(
            $this->driverFactory->create($connection, $config),
            $this->securityAgnosticTopics,
            $this->tokenProvider,
            $this->tokenSerializer
        );
    }
}
