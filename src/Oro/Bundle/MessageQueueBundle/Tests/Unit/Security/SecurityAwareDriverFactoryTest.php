<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Security;

use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver;
use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriverFactory;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverFactoryInterface;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class SecurityAwareDriverFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $config = $this->createMock(Config::class);
        $driver = $this->createMock(DriverInterface::class);

        /** @var MockObject|DriverFactoryInterface */
        $driverFactory = $this->createMock(DriverFactoryInterface::class);

        /** @var MockObject|TokenStorageInterface */
        $tokenStorage = $this->createMock(TokenStorageInterface::class);

        /** @var MockObject|TokenSerializerInterface */
        $tokenSerializer = $this->createMock(TokenSerializerInterface::class);

        /** @var SecurityAwareDriverFactory */
        $securityAwareDriverFactory = new SecurityAwareDriverFactory(
            $driverFactory,
            ['security_agnostic_topic'],
            $tokenStorage,
            $tokenSerializer
        );

        $driverFactory->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($connection), self::identicalTo($config))
            ->willReturn($driver);

        $result = $securityAwareDriverFactory->create($connection, $config);

        self::assertInstanceOf(SecurityAwareDriver::class, $result);

        $driverProperty = new \ReflectionProperty(SecurityAwareDriver::class, 'driver');
        $driverProperty->setAccessible(true);
        $securityAgnosticTopicsProperty = new \ReflectionProperty(SecurityAwareDriver::class, 'securityAgnosticTopics');
        $securityAgnosticTopicsProperty->setAccessible(true);
        $tokenStorageProperty = new \ReflectionProperty(SecurityAwareDriver::class, 'tokenStorage');
        $tokenStorageProperty->setAccessible(true);
        $tokenSerializerProperty = new \ReflectionProperty(SecurityAwareDriver::class, 'tokenSerializer');
        $tokenSerializerProperty->setAccessible(true);

        self::assertSame($driver, $driverProperty->getValue($result));
        self::assertEquals(['security_agnostic_topic' => true], $securityAgnosticTopicsProperty->getValue($result));
        self::assertSame($tokenStorage, $tokenStorageProperty->getValue($result));
        self::assertSame($tokenSerializer, $tokenSerializerProperty->getValue($result));
    }
}
