<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Client;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Client\DbalTransactionWatcher;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DbalTransactionWatcherTest extends TestCase
{
    private BufferedMessageProducer&MockObject $bufferedProducer;
    private DbalTransactionWatcher $transactionWatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->bufferedProducer = $this->createMock(BufferedMessageProducer::class);
        $this->transactionWatcher = new DbalTransactionWatcher($this->bufferedProducer);
    }

    public function testShouldEnableBufferingWhenTransactionStarted(): void
    {
        $this->bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $this->transactionWatcher->onTransactionStarted();
    }

    public function testShouldFlushBufferAndThenDisableBufferingWhenTransactionCommitted(): void
    {
        $this->bufferedProducer->expects(self::once())
            ->method('flushBuffer');
        $this->bufferedProducer->expects(self::once())
            ->method('disableBuffering');

        $this->transactionWatcher->onTransactionCommitted();
    }

    public function testShouldDisableBufferingEvenIfFlushBufferFailedWhenTransactionCommitted(): void
    {
        $exception = new \Exception('some error');

        $this->bufferedProducer->expects(self::once())
            ->method('flushBuffer')
            ->willThrowException($exception);
        $this->bufferedProducer->expects(self::once())
            ->method('disableBuffering');

        try {
            $this->transactionWatcher->onTransactionCommitted();
            self::fail('The exception should not be caught');
        } catch (AssertionFailedError $e) {
            throw $e;
        } catch (\Exception $e) {
            self::assertSame($exception, $e);
        }
    }

    public function testShouldClearBufferAndThenDisableBufferingWhenTransactionRolledback(): void
    {
        $this->bufferedProducer->expects(self::once())
            ->method('clearBuffer');
        $this->bufferedProducer->expects(self::once())
            ->method('disableBuffering');

        $this->transactionWatcher->onTransactionRolledback();
    }
}
