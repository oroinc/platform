<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Client;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Client\DbalTransactionWatcher;

class DbalTransactionWatcherTest extends \PHPUnit\Framework\TestCase
{
    /** @var BufferedMessageProducer|\PHPUnit\Framework\MockObject\MockObject */
    private $bufferedProducer;

    /** @var DbalTransactionWatcher */
    private $transactionWatcher;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->bufferedProducer = $this->createMock(BufferedMessageProducer::class);
        $this->transactionWatcher = new DbalTransactionWatcher($this->bufferedProducer);
    }

    public function testShouldEnableBufferingWhenTransactionStarted()
    {
        $this->bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        $this->transactionWatcher->onTransactionStarted();
    }

    public function testShouldFlushBufferAndThenDisableBufferingWhenTransactionCommitted()
    {
        $this->bufferedProducer->expects(self::once())
            ->method('flushBuffer');
        $this->bufferedProducer->expects(self::once())
            ->method('disableBuffering');

        $this->transactionWatcher->onTransactionCommitted();
    }

    public function testShouldDisableBufferingEvenIfFlushBufferFailedWhenTransactionCommitted()
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
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            throw $e;
        } catch (\Exception $e) {
            self::assertSame($exception, $e);
        }
    }

    public function testShouldClearBufferAndThenDisableBufferingWhenTransactionRolledback()
    {
        $this->bufferedProducer->expects(self::once())
            ->method('clearBuffer');
        $this->bufferedProducer->expects(self::once())
            ->method('disableBuffering');

        $this->transactionWatcher->onTransactionRolledback();
    }
}
