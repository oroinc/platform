<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Monolog\Handler\HandlerInterface;
use Oro\Bundle\LoggerBundle\Monolog\ErrorLogNotificationHandlerWrapper;
use Oro\Bundle\LoggerBundle\Provider\ErrorLogNotificationRecipientsProvider;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Exception\RfcComplianceException;

class ErrorLogNotificationHandlerWrapperTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private HandlerInterface $innerHandler;

    private ErrorLogNotificationRecipientsProvider|\PHPUnit\Framework\MockObject\MockObject $recipientsProvider;

    private ErrorLogNotificationHandlerWrapper $handlerWrapper;

    protected function setUp(): void
    {
        $this->recipientsProvider = $this->createMock(ErrorLogNotificationRecipientsProvider::class);
        $this->innerHandler = $this->createMock(HandlerInterface::class);

        $this->handlerWrapper = new ErrorLogNotificationHandlerWrapper($this->innerHandler, $this->recipientsProvider);

        $this->setUpLoggerMock($this->handlerWrapper);
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

    /**
     * @dataProvider throwableDataProvider
     *
     * @param \Throwable $throwable
     */
    public function testHandleBatchLogsWarningWhenCannotSendEmail(\Throwable $throwable): void
    {
        $this->recipientsProvider
            ->expects(self::once())
            ->method('getRecipientsEmailAddresses')
            ->willReturn(['to@example.org']);

        $records = ['sample_record'];
        $this->innerHandler
            ->expects(self::atLeastOnce())
            ->method('handleBatch')
            ->with($records)
            ->willThrowException($throwable);

        $this->loggerMock
            ->expects(self::exactly(2))
            ->method('warning')
            ->with(
                self::stringStartsWith('Failed to send error log email notification:'),
                ['throwable' => $throwable, 'records' => $records]
            );

        $this->handlerWrapper->handleBatch($records);

        // Checks caching.
        $this->handlerWrapper->handleBatch($records);
    }

    public function throwableDataProvider(): array
    {
        return [
            ['throwable' => new RfcComplianceException()],
            ['throwable' => new InvalidArgumentException()],
        ];
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

        $record = ['sample_record'];
        $this->innerHandler
            ->expects(self::atLeastOnce())
            ->method('handle')
            ->with($record);

        $this->handlerWrapper->handle($record);

        // Checks caching.
        $this->handlerWrapper->handle($record);
    }

    /**
     * @dataProvider throwableDataProvider
     *
     * @param \Throwable $throwable
     */
    public function testHandleLogsWarningWhenCannotSendEmail(\Throwable $throwable): void
    {
        $this->recipientsProvider
            ->expects(self::once())
            ->method('getRecipientsEmailAddresses')
            ->willReturn(['to@example.org']);

        $record = ['sample_record'];
        $this->innerHandler
            ->expects(self::atLeastOnce())
            ->method('handle')
            ->with($record)
            ->willThrowException($throwable);

        $this->loggerMock
            ->expects(self::exactly(2))
            ->method('warning')
            ->with(
                self::stringStartsWith('Failed to send error log email notification:'),
                ['throwable' => $throwable, 'record' => $record]
            );

        $this->handlerWrapper->handle($record);

        // Checks caching.
        $this->handlerWrapper->handle($record);
    }

    public function testIsHandlingWhenHandleAndNoRecipients(): void
    {
        $this->recipientsProvider
            ->expects(self::once())
            ->method('getRecipientsEmailAddresses')
            ->willReturn([]);

        $record = ['sample_record'];
        $this->innerHandler
            ->expects(self::atLeastOnce())
            ->method('isHandling')
            ->with($record)
            ->willReturn(true);

        self::assertFalse($this->handlerWrapper->isHandling($record));

        // Checks caching.
        self::assertFalse($this->handlerWrapper->isHandling($record));
    }

    public function testIsHandlingWhenHandleAndHasRecipients(): void
    {
        $this->recipientsProvider
            ->expects(self::once())
            ->method('getRecipientsEmailAddresses')
            ->willReturn(['to@example.org']);

        $record = ['sample_record'];
        $this->innerHandler
            ->expects(self::atLeastOnce())
            ->method('isHandling')
            ->with($record)
            ->willReturn(true);

        self::assertTrue($this->handlerWrapper->isHandling($record));

        // Checks caching.
        self::assertTrue($this->handlerWrapper->isHandling($record));
    }

    public function testIsHandlingWhenNotHandle(): void
    {
        $this->recipientsProvider
            ->expects(self::never())
            ->method('getRecipientsEmailAddresses');

        $record = ['sample_record'];
        $this->innerHandler
            ->expects(self::once())
            ->method('isHandling')
            ->with($record)
            ->willReturn(false);

        self::assertFalse($this->handlerWrapper->isHandling($record));
    }
}
