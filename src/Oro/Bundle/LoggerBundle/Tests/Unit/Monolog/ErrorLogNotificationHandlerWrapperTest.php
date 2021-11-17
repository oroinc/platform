<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Monolog\Handler\HandlerInterface;
use Oro\Bundle\LoggerBundle\Monolog\ErrorLogNotificationHandlerWrapper;
use Oro\Bundle\LoggerBundle\Provider\ErrorLogNotificationRecipientsProvider;

class ErrorLogNotificationHandlerWrapperTest extends \PHPUnit\Framework\TestCase
{
    private HandlerInterface $innerHandler;

    private ErrorLogNotificationRecipientsProvider|\PHPUnit\Framework\MockObject\MockObject $recipientsProvider;

    private ErrorLogNotificationHandlerWrapper $handlerWrapper;

    protected function setUp(): void
    {
        $this->recipientsProvider = $this->createMock(ErrorLogNotificationRecipientsProvider::class);
        $this->innerHandler = $this->createMock(HandlerInterface::class);

        $this->handlerWrapper = new ErrorLogNotificationHandlerWrapper($this->innerHandler, $this->recipientsProvider);
    }

    public function testHandleBatchWhenNoRecipients(): void
    {
        $this->recipientsProvider
            ->expects(self::once())
            ->method('getRecipientsEmailAddresses')
            ->willReturn([]);

        $this->innerHandler
            ->expects(self::never())
            ->method(self::anything());

        $this->handlerWrapper->handleBatch([]);

        // Checks caching.
        $this->handlerWrapper->handleBatch([]);
    }

    public function testHandleBatchWhenHasRecipients(): void
    {
        $this->recipientsProvider
            ->expects(self::once())
            ->method('getRecipientsEmailAddresses')
            ->willReturn(['to@example.org']);

        $records = ['sample_record'];
        $this->innerHandler
            ->expects(self::atLeastOnce())
            ->method('handleBatch')
            ->with($records);

        $this->handlerWrapper->handleBatch($records);

        // Checks caching.
        $this->handlerWrapper->handleBatch($records);
    }

    public function testHandleWhenNoRecipients(): void
    {
        $this->recipientsProvider
            ->expects(self::once())
            ->method('getRecipientsEmailAddresses')
            ->willReturn([]);

        $this->innerHandler
            ->expects(self::never())
            ->method(self::anything());

        $this->handlerWrapper->handle([]);

        // Checks caching.
        $this->handlerWrapper->handle([]);
    }

    public function testHandleWhenHasRecipients(): void
    {
        $this->recipientsProvider
            ->expects(self::once())
            ->method('getRecipientsEmailAddresses')
            ->willReturn(['to@example.org']);

        $records = ['sample_record'];
        $this->innerHandler
            ->expects(self::atLeastOnce())
            ->method('handle')
            ->with($records);

        $this->handlerWrapper->handle($records);

        // Checks caching.
        $this->handlerWrapper->handle($records);
    }

    public function testIsHandlingWhenHandleAndNoRecipients(): void
    {
        $this->recipientsProvider
            ->expects(self::once())
            ->method('getRecipientsEmailAddresses')
            ->willReturn([]);

        $records = ['sample_record'];
        $this->innerHandler
            ->expects(self::atLeastOnce())
            ->method('isHandling')
            ->with($records)
            ->willReturn(true);

        self::assertFalse($this->handlerWrapper->isHandling($records));

        // Checks caching.
        self::assertFalse($this->handlerWrapper->isHandling($records));
    }

    public function testIsHandlingWhenHandleAndHasRecipients(): void
    {
        $this->recipientsProvider
            ->expects(self::once())
            ->method('getRecipientsEmailAddresses')
            ->willReturn(['to@example.org']);

        $records = ['sample_record'];
        $this->innerHandler
            ->expects(self::atLeastOnce())
            ->method('isHandling')
            ->with($records)
            ->willReturn(true);

        self::assertTrue($this->handlerWrapper->isHandling($records));

        // Checks caching.
        self::assertTrue($this->handlerWrapper->isHandling($records));
    }

    public function testIsHandlingWhenNotHandle(): void
    {
        $this->recipientsProvider
            ->expects(self::never())
            ->method('getRecipientsEmailAddresses');

        $records = ['sample_record'];
        $this->innerHandler
            ->expects(self::once())
            ->method('isHandling')
            ->with($records)
            ->willReturn(false);

        self::assertFalse($this->handlerWrapper->isHandling($records));
    }
}
