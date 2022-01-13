<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Async;

use Oro\Bundle\TranslationBundle\Async\DumpJsTranslationsMessageProcessor;
use Oro\Bundle\TranslationBundle\Async\Topic\DumpJsTranslationsTopic;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Exception\IOException;

class DumpJsTranslationsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    private JsTranslationDumper|\PHPUnit\Framework\MockObject\MockObject $dumper;

    private DumpJsTranslationsMessageProcessor $processor;

    protected function setUp(): void
    {
        $logger = new NullLogger();
        $this->dumper = $this->createMock(JsTranslationDumper::class);
        $this->dumper->expects($this->any())
            ->method('setLogger')
            ->with($logger);

        $this->processor = new DumpJsTranslationsMessageProcessor($this->dumper, $logger);
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([DumpJsTranslationsTopic::getName()], $this->processor->getSubscribedTopics());
    }

    public function testProcess()
    {
        $this->dumper->expects($this->once())
            ->method('dumpTranslations');

        $result = $this->processor->process(
            $this->createMock(MessageInterface::class),
            $this->createMock(SessionInterface::class)
        );

        $this->assertEquals(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithDumperCrash()
    {
        $this->dumper->expects($this->once())
            ->method('dumpTranslations')
            ->willThrowException(new IOException('test'));

        $result = $this->processor->process(
            $this->createMock(MessageInterface::class),
            $this->createMock(SessionInterface::class)
        );

        $this->assertEquals(MessageProcessorInterface::REJECT, $result);
    }
}
