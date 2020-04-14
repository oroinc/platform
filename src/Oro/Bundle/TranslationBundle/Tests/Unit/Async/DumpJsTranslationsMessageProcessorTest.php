<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Async;

use Oro\Bundle\TranslationBundle\Async\DumpJsTranslationsMessageProcessor;
use Oro\Bundle\TranslationBundle\Async\Topics;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Exception\IOException;

class DumpJsTranslationsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var DumpJsTranslationsMessageProcessor  */
    private $processor;

    /** @var JsTranslationDumper|\PHPUnit\Framework\MockObject\MockObject */
    private $dumper;

    /**
     * {@inheritdoc}
     */
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
        $this->assertEquals([Topics::JS_TRANSLATIONS_DUMP], $this->processor->getSubscribedTopics());
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

    public function testProcessWhithDumperCrash()
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
