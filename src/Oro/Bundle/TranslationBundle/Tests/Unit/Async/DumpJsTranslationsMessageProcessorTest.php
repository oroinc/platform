<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Async;

use Oro\Bundle\TranslationBundle\Async\DumpJsTranslationsMessageProcessor;
use Oro\Bundle\TranslationBundle\Async\Topic\DumpJsTranslationsTopic;
use Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Logger\BufferingLogger;
use Symfony\Component\Filesystem\Exception\IOException;

class DumpJsTranslationsMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var JsTranslationDumper|\PHPUnit\Framework\MockObject\MockObject */
    private $dumper;

    /** @var BufferingLogger */
    private $logger;

    /** @var DumpJsTranslationsMessageProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->dumper = $this->createMock(JsTranslationDumper::class);
        $this->logger = new BufferingLogger();

        $this->processor = new DumpJsTranslationsMessageProcessor($this->dumper, $this->logger);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertEquals([DumpJsTranslationsTopic::getName()], $this->processor->getSubscribedTopics());
    }

    public function testProcess(): void
    {
        $this->dumper->expects(self::once())
            ->method('getAllLocales')
            ->willReturn(['en', 'en_US']);
        $this->dumper->expects(self::exactly(2))
            ->method('dumpTranslationFile')
            ->withConsecutive(['en'], ['en_US'])
            ->willReturnOnConsecutiveCalls('en.json', 'en_us.json');

        $result = $this->processor->process(
            $this->createMock(MessageInterface::class),
            $this->createMock(SessionInterface::class)
        );

        self::assertEquals(MessageProcessorInterface::ACK, $result);

        self::assertEquals(
            [
                [
                    'info',
                    'Update JS translations for {locale}.',
                    ['locale' => 'en']
                ],
                [
                    'info',
                    'The JS translations for {locale} have been dumped into {file}.',
                    ['locale' => 'en', 'file' => 'en.json']
                ],
                [
                    'info',
                    'Update JS translations for {locale}.',
                    ['locale' => 'en_US']
                ],
                [
                    'info',
                    'The JS translations for {locale} have been dumped into {file}.',
                    ['locale' => 'en_US', 'file' => 'en_us.json']
                ]
            ],
            $this->logger->cleanLogs()
        );
    }

    public function testProcessWithDumperCrash(): void
    {
        $exception = new IOException('some error');

        $this->dumper->expects(self::once())
            ->method('getAllLocales')
            ->willReturn(['en', 'en_US']);
        $this->dumper->expects(self::once())
            ->method('dumpTranslationFile')
            ->with('en')
            ->willThrowException($exception);

        $result = $this->processor->process(
            $this->createMock(MessageInterface::class),
            $this->createMock(SessionInterface::class)
        );

        self::assertEquals(MessageProcessorInterface::REJECT, $result);

        self::assertEquals(
            [
                [
                    'info',
                    'Update JS translations for {locale}.',
                    ['locale' => 'en']
                ],
                [
                    'error',
                    'Cannot update JS translations.',
                    ['exception' => $exception]
                ]
            ],
            $this->logger->cleanLogs()
        );
    }
}
