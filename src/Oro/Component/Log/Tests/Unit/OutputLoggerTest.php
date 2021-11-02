<?php

namespace Oro\Component\Log\Tests\Unit;

use Oro\Component\Log\OutputLogger;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Output\OutputInterface;

class OutputLoggerTest extends \PHPUnit\Framework\TestCase
{
    /** @var OutputInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $output;

    protected function setUp(): void
    {
        $this->output = $this->createMock(OutputInterface::class);
    }

    /**
     * @dataProvider itemProvider
     */
    public function testLog(
        bool $expectWriteToOutput,
        int $verbosity,
        string $level,
        string $message,
        array $context
    ) {
        $this->output->expects($this->any())
            ->method('getVerbosity')
            ->willReturn($verbosity);

        if ($expectWriteToOutput) {
            if (isset($context['exception']) && $context['exception'] instanceof \Exception) {
                $this->output->expects($this->exactly(2))
                    ->method('writeln');
            } else {
                $this->output->expects($this->once())
                    ->method('writeln');
            }
        } else {
            $this->output->expects($this->never())
                ->method('writeln');
        }

        $logger = new OutputLogger($this->output);
        $logger->log($level, $message, $context);
    }

    /**
     * @dataProvider withTagsProvider
     */
    public function testLogWithTags(string $level, string $message, string $expected)
    {
        $this->output->expects($this->once())
            ->method('getVerbosity')
            ->willReturn(OutputInterface::VERBOSITY_DEBUG);

        $this->output->expects($this->once())
            ->method('writeln')
            ->with($expected);

        $logger = new OutputLogger($this->output, true, null, null, true);
        $logger->log($level, $message);
    }

    public function withTagsProvider(): array
    {
        return [
            [LogLevel::EMERGENCY, 'test', '<error>[emergency]</error> test'],
            [LogLevel::WARNING, 'test', '<comment>[warning]</comment> test'],
            [LogLevel::NOTICE, 'test', '<info>[notice]</info> test'],
        ];
    }

    public function itemProvider(): array
    {
        return [
            [
                true,
                OutputInterface::VERBOSITY_QUIET,
                LogLevel::EMERGENCY,
                'test',
                ['exception' => new \Exception()]
            ],
            [true, OutputInterface::VERBOSITY_QUIET, LogLevel::EMERGENCY, 'test', []],
            [true, OutputInterface::VERBOSITY_NORMAL, LogLevel::EMERGENCY, 'test', []],
            [true, OutputInterface::VERBOSITY_VERBOSE, LogLevel::EMERGENCY, 'test', []],
            [true, OutputInterface::VERBOSITY_VERY_VERBOSE, LogLevel::EMERGENCY, 'test', []],
            [true, OutputInterface::VERBOSITY_DEBUG, LogLevel::EMERGENCY, 'test', []],

            [
                false,
                OutputInterface::VERBOSITY_QUIET,
                LogLevel::ALERT,
                'test',
                ['exception' => new \Exception()]
            ],
            [false, OutputInterface::VERBOSITY_QUIET, LogLevel::ALERT, 'test', []],
            [false, OutputInterface::VERBOSITY_NORMAL, LogLevel::ALERT, 'test', []],
            [false, OutputInterface::VERBOSITY_VERBOSE, LogLevel::ALERT, 'test', []],
            [true, OutputInterface::VERBOSITY_VERY_VERBOSE, LogLevel::ALERT, 'test', []],
            [true, OutputInterface::VERBOSITY_DEBUG, LogLevel::ALERT, 'test', []],

            [
                true,
                OutputInterface::VERBOSITY_QUIET,
                LogLevel::CRITICAL,
                'test',
                ['exception' => new \Exception()]
            ],
            [true, OutputInterface::VERBOSITY_QUIET, LogLevel::CRITICAL, 'test', []],
            [true, OutputInterface::VERBOSITY_NORMAL, LogLevel::CRITICAL, 'test', []],
            [true, OutputInterface::VERBOSITY_VERBOSE, LogLevel::CRITICAL, 'test', []],
            [true, OutputInterface::VERBOSITY_VERY_VERBOSE, LogLevel::CRITICAL, 'test', []],
            [true, OutputInterface::VERBOSITY_DEBUG, LogLevel::CRITICAL, 'test', []],

            [
                true,
                OutputInterface::VERBOSITY_QUIET,
                LogLevel::ERROR,
                'test',
                ['exception' => new \Exception()]
            ],
            [true, OutputInterface::VERBOSITY_QUIET, LogLevel::ERROR, 'test', []],
            [true, OutputInterface::VERBOSITY_NORMAL, LogLevel::ERROR, 'test', []],
            [true, OutputInterface::VERBOSITY_VERBOSE, LogLevel::ERROR, 'test', []],
            [true, OutputInterface::VERBOSITY_VERY_VERBOSE, LogLevel::ERROR, 'test', []],
            [true, OutputInterface::VERBOSITY_DEBUG, LogLevel::ERROR, 'test', []],

            [false, OutputInterface::VERBOSITY_QUIET, LogLevel::WARNING, 'test', []],
            [true,  OutputInterface::VERBOSITY_NORMAL, LogLevel::WARNING, 'test', []],
            [true,  OutputInterface::VERBOSITY_VERBOSE, LogLevel::WARNING, 'test', []],
            [true,  OutputInterface::VERBOSITY_VERY_VERBOSE, LogLevel::WARNING, 'test', []],
            [true,  OutputInterface::VERBOSITY_DEBUG, LogLevel::WARNING, 'test', []],

            [false, OutputInterface::VERBOSITY_QUIET, LogLevel::NOTICE, 'test', []],
            [false,  OutputInterface::VERBOSITY_NORMAL, LogLevel::NOTICE, 'test', []],
            [false,  OutputInterface::VERBOSITY_VERBOSE, LogLevel::NOTICE, 'test', []],
            [true,  OutputInterface::VERBOSITY_VERY_VERBOSE, LogLevel::NOTICE, 'test', []],
            [true,  OutputInterface::VERBOSITY_DEBUG, LogLevel::NOTICE, 'test', []],

            [false, OutputInterface::VERBOSITY_QUIET, LogLevel::INFO, 'test', []],
            [true, OutputInterface::VERBOSITY_NORMAL, LogLevel::INFO, 'test', []],
            [true,  OutputInterface::VERBOSITY_VERBOSE, LogLevel::INFO, 'test', []],
            [true,  OutputInterface::VERBOSITY_VERY_VERBOSE, LogLevel::INFO, 'test', []],
            [true,  OutputInterface::VERBOSITY_DEBUG, LogLevel::INFO, 'test', []],

            [false, OutputInterface::VERBOSITY_QUIET, LogLevel::DEBUG, 'test', []],
            [false, OutputInterface::VERBOSITY_NORMAL, LogLevel::DEBUG, 'test', []],
            [false, OutputInterface::VERBOSITY_VERBOSE, LogLevel::DEBUG, 'test', []],
            [false, OutputInterface::VERBOSITY_VERY_VERBOSE, LogLevel::DEBUG, 'test', []],
            [true,  OutputInterface::VERBOSITY_DEBUG, LogLevel::DEBUG, 'test', []],
        ];
    }
}
