<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\TokenStorageClearerExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;

class TokenStorageClearerExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        $extension = new TokenStorageClearerExtension($this->createTokenStorageInterfaceMock());

        $this->assertInstanceOf(ExtensionInterface::class, $extension);
    }

    public function testShouldClearTokenStorage()
    {
        $tokenStorage = $this->createTokenStorageInterfaceMock();
        $tokenStorage
            ->expects($this->once())
            ->method('setToken')
            ->with(null);

        $context = new Context($this->createSessionMock());
        $extension = new TokenStorageClearerExtension($tokenStorage);
        $extension->onPostReceived($context);
    }

    public function testShouldNotClearTokenStorage()
    {
        $tokenStorage = $this->createTokenStorageInterfaceMock();
        $tokenStorage
            ->expects($this->never())
            ->method('setToken');

        $context = new Context($this->createSessionMock());
        $extension = new TokenStorageClearerExtension($tokenStorage);
        $extension->onStart($context);
        $extension->onBeforeReceive($context);
        $extension->onPreReceived($context);
        $extension->onIdle($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected function createTokenStorageInterfaceMock()
    {
        return $this->createMock(TokenStorageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    protected function createSessionMock()
    {
        return $this->createMock(SessionInterface::class);
    }
}
