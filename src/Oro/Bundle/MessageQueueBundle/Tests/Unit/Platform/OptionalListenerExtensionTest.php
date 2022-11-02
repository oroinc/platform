<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Platform;

use Oro\Bundle\MessageQueueBundle\Platform\OptionalListenerExtension;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PlatformBundle\Provider\Console\OptionalListenersGlobalOptionsProvider;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Consumption\Context;

class OptionalListenerExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldAlwaysReEnableListeners()
    {
        $optionalListenerManager = $this->createMock(OptionalListenerManager::class);
        $optionalListenerManager->expects($this->once())->method('getListeners')->willReturn([]);
        $optionalListenerManager->expects($this->once())->method('enableListeners');
        $optionalListenerManager->expects($this->never())->method('disableListener');

        $message = new Message();
        $context = $this->createMock(Context::class);
        $context->expects($this->once())->method('getMessage')->willReturn($message);

        $extension = new OptionalListenerExtension($optionalListenerManager);
        $extension->onPreReceived($context);
    }

    public function testInvalidJson()
    {
        $optionalListenerManager = $this->createMock(OptionalListenerManager::class);
        $optionalListenerManager->expects($this->once())->method('getListeners')->willReturn([]);
        $optionalListenerManager->expects($this->once())->method('enableListeners');
        $optionalListenerManager->expects($this->never())->method('disableListener');

        $message = new Message();
        $message->setProperty(
            OptionalListenersGlobalOptionsProvider::DISABLE_OPTIONAL_LISTENERS,
            '<s>{&quot;0&quot;:1,&quot;1&quot;:2}</s>'
        );
        $context = $this->createMock(Context::class);
        $context->expects($this->once())->method('getMessage')->willReturn($message);

        $extension = new OptionalListenerExtension($optionalListenerManager);
        $extension->onPreReceived($context);
    }

    public function testDisableListeners()
    {
        $optionalListenerManager = $this->createMock(OptionalListenerManager::class);
        $optionalListenerManager->expects($this->once())->method('getListeners')->willReturn([]);
        $optionalListenerManager->expects($this->once())->method('enableListeners');
        $optionalListenerManager
            ->expects($this->once())
            ->method('disableListener')
            ->willReturn('oro_search.index_listener');

        $message = new Message();
        $message->setProperty(
            OptionalListenersGlobalOptionsProvider::DISABLE_OPTIONAL_LISTENERS,
            json_encode(['oro_search.index_listener'])
        );
        $context = $this->createMock(Context::class);
        $context->expects($this->once())->method('getMessage')->willReturn($message);

        $extension = new OptionalListenerExtension($optionalListenerManager);
        $extension->onPreReceived($context);
    }

    public function testSuppressNotExistingListener()
    {
        $optionalListenerManager = $this->createMock(OptionalListenerManager::class);
        $optionalListenerManager->expects($this->once())->method('getListeners')->willReturn([]);
        $optionalListenerManager->expects($this->once())->method('enableListeners');
        $optionalListenerManager
            ->expects($this->once())
            ->method('disableListener')
            ->willThrowException(new \InvalidArgumentException());

        $message = new Message();
        $message->setProperty(
            OptionalListenersGlobalOptionsProvider::DISABLE_OPTIONAL_LISTENERS,
            json_encode(['oro_search.index_listener'])
        );
        $context = $this->createMock(Context::class);
        $context->expects($this->once())->method('getMessage')->willReturn($message);

        $extension = new OptionalListenerExtension($optionalListenerManager);
        $extension->onPreReceived($context);
    }
}
