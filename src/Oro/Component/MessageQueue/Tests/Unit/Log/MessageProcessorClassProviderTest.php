<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Log;

use Oro\Component\MessageQueue\Client\MessageProcessorRegistryInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Log\MessageProcessorClassProvider;
use Oro\Component\MessageQueue\Tests\Unit\Log\Processor\Stub\MessageProcessorLazyLoadingProxy;
use Oro\Component\MessageQueue\Tests\Unit\Log\Processor\Stub\MessageProcessorProxy;

class MessageProcessorClassProviderTest extends \PHPUnit\Framework\TestCase
{
    private MessageProcessorRegistryInterface|\PHPUnit\Framework\MockObject\MockObject $messageProcessorRegistry;

    private MessageProcessorClassProvider $messageProcessorClassProvider;

    protected function setUp(): void
    {
        $this->messageProcessorRegistry = $this->createMock(MessageProcessorRegistryInterface::class);
        $this->messageProcessorClassProvider = new MessageProcessorClassProvider($this->messageProcessorRegistry);
    }

    public function testGetMessageProcessorClassByNameForValueHolderService(): void
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $messageProcessorProxy = new MessageProcessorProxy($messageProcessor);
        $messageProcessorName = 'sample_processor';

        $this->messageProcessorRegistry
            ->expects(self::once())
            ->method('get')
            ->with($messageProcessorName)
            ->willReturn($messageProcessorProxy);

        self::assertEquals(
            get_class($messageProcessor),
            $this->messageProcessorClassProvider->getMessageProcessorClassByName($messageProcessorName)
        );

        // Checks local caching.
        self::assertEquals(
            get_class($messageProcessor),
            $this->messageProcessorClassProvider->getMessageProcessorClassByName($messageProcessorName)
        );
    }

    /**
     * @dataProvider isInitializedDataProvider
     *
     * @param bool $isInitialized
     */
    public function testGetMessageProcessorClassByNameForLazyLoadingService(bool $isInitialized): void
    {
        $messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $messageProcessorLazyLoadingProxy = new MessageProcessorLazyLoadingProxy($messageProcessor, $isInitialized);
        $messageProcessorName = 'sample_processor';

        $this->messageProcessorRegistry
            ->expects(self::once())
            ->method('get')
            ->with($messageProcessorName)
            ->willReturn($messageProcessorLazyLoadingProxy);

        self::assertEquals(
            get_class($messageProcessor),
            $this->messageProcessorClassProvider->getMessageProcessorClassByName($messageProcessorName)
        );

        // Checks local caching.
        self::assertEquals(
            get_class($messageProcessor),
            $this->messageProcessorClassProvider->getMessageProcessorClassByName($messageProcessorName)
        );
    }

    public function isInitializedDataProvider(): array
    {
        return [[true], [false]];
    }
}
