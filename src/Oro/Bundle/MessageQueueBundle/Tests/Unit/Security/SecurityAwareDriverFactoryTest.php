<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverFactoryInterface;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriver;
use Oro\Bundle\MessageQueueBundle\Security\SecurityAwareDriverFactory;
use Oro\Bundle\SecurityBundle\Authentication\TokenSerializerInterface;

class SecurityAwareDriverFactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DriverFactoryInterface */
    private $driverFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface */
    private $tokenStorage;

    /** @var \PHPUnit_Framework_MockObject_MockObject|TokenSerializerInterface */
    private $tokenSerializer;

    /** @var SecurityAwareDriverFactory */
    private $securityAwareDriverFactory;

    protected function setUp()
    {
        $this->driverFactory = $this->createMock(DriverFactoryInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->tokenSerializer = $this->createMock(TokenSerializerInterface::class);

        $this->securityAwareDriverFactory = new SecurityAwareDriverFactory(
            $this->driverFactory,
            ['security_agnostic_topic'],
            $this->tokenStorage,
            $this->tokenSerializer
        );
    }

    public function testCreate()
    {
        $connection = $this->createMock(ConnectionInterface::class);
        $config = $this->createMock(Config::class);
        $driver = $this->createMock(DriverInterface::class);

        $this->driverFactory->expects(self::once())
            ->method('create')
            ->with(self::identicalTo($connection), self::identicalTo($config))
            ->willReturn($driver);

        $result = $this->securityAwareDriverFactory->create($connection, $config);

        self::assertInstanceOf(SecurityAwareDriver::class, $result);
        self::assertAttributeSame($driver, 'driver', $result);
        self::assertAttributeEquals(['security_agnostic_topic' => true], 'securityAgnosticTopics', $result);
        self::assertAttributeSame($this->tokenStorage, 'tokenStorage', $result);
        self::assertAttributeSame($this->tokenSerializer, 'tokenSerializer', $result);
    }
}
