<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Security;

use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver;
use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriverFactory;
use Oro\Bundle\MessageQueueBundle\Security\SecurityTokenProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverFactoryInterface;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\Testing\ReflectionUtil;

class SecurityAwareDriverFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $config = $this->createMock(Config::class);
        $driver = $this->createMock(DriverInterface::class);

        $driverFactory = $this->createMock(DriverFactoryInterface::class);
        $tokenProvider = $this->createMock(SecurityTokenProviderInterface::class);
        $tokenSerializer = $this->createMock(TokenSerializerInterface::class);

        /** @var SecurityAwareDriverFactory */
        $securityAwareDriverFactory = new SecurityAwareDriverFactory(
            $driverFactory,
            ['security_agnostic_topic'],
            $tokenProvider,
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
        self::assertSame($tokenProvider, ReflectionUtil::getPropertyValue($result, 'tokenProvider'));
        self::assertSame($tokenSerializer, ReflectionUtil::getPropertyValue($result, 'tokenSerializer'));
    }
}
