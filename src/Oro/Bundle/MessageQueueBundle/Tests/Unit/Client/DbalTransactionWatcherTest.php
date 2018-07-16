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
    protected function setUp()
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

    public function testShouldFlushBufferAndThenDisableBufferingWhenTransactionCommited()
    {
        $this->bufferedProducer->expects(self::at(0))
            ->method('flushBuffer');
        $this->bufferedProducer->expects(self::at(1))
            ->method('disableBuffering');

        $this->transactionWatcher->onTransactionCommited();
    }

    public function testShouldDisableBufferingEvenIfFlushBufferFailedWhenTransactionCommited()
    {
        $exception = new \Exception('some error');

        $this->bufferedProducer->expects(self::at(0))
            ->method('flushBuffer')
            ->willThrowException($exception);
        $this->bufferedProducer->expects(self::at(1))
            ->method('disableBuffering');

        try {
            $this->transactionWatcher->onTransactionCommited();
            self::fail('The exception should not be catched');
        } catch (\PHPUnit\Framework\AssertionFailedError $e) {
            throw $e;
        } catch (\Exception $e) {
            self::assertSame($exception, $e);
        }
    }

    public function testShouldClearBufferAndThenDisableBufferingWhenTransactionRolledback()
    {
        $this->bufferedProducer->expects(self::at(0))
            ->method('clearBuffer');
        $this->bufferedProducer->expects(self::at(1))
            ->method('disableBuffering');

        $this->transactionWatcher->onTransactionRolledback();
    }
}
