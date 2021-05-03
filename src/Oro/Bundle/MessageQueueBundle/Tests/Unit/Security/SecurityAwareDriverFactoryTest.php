<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Security;

use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver;
use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriverFactory;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverFactoryInterface;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\Testing\ReflectionUtil;
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
        self::assertSame($driver, ReflectionUtil::getPropertyValue($result, 'driver'));
        self::assertEquals(
            ['security_agnostic_topic' => true],
            ReflectionUtil::getPropertyValue($result, 'securityAgnosticTopics')
        );
        self::assertSame($tokenStorage, ReflectionUtil::getPropertyValue($result, 'tokenStorage'));
        self::assertSame($tokenSerializer, ReflectionUtil::getPropertyValue($result, 'tokenSerializer'));
    }
}
