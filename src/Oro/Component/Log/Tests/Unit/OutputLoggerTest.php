<?php

namespace Oro\Component\Log\Tests\Unit\OutputLoggerTest;

use Psr\Log\LogLevel;

use Symfony\Component\Console\Output\OutputInterface;

use Oro\Component\Log\Logger\OutputLogger;

class OutputLoggerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $output;

    /** @var OutputLogger */
    protected $logger;

    protected function setUp()
    {
        $this->output = $this->getMock('Symfony\Component\Console\Output\OutputInterface');
        $this->logger = new OutputLogger($this->output);
    }

    /**
     * @dataProvider itemProvider
     */
    public function testLog($expectWriteToOutput, $verbosity, $level, $message, $context)
    {
        $this->output->expects($this->any())
            ->method('getVerbosity')
            ->will($this->returnValue($verbosity));

        if ($expectWriteToOutput) {
            if (isset($context['exception']) && $context['exception'] instanceof \Exception) {
                $this->output->expects($this->exactly(2))
                    ->method('writeln');
            } else {
                $this->output->expects($this->exactly(1))
                    ->method('writeln');
            }
        } else {
            $this->output->expects($this->never())
                ->method('writeln');
        }

        $this->logger->log($level, $message, $context);
    }

    public function itemProvider()
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
                true,
                OutputInterface::VERBOSITY_QUIET,
                LogLevel::ALERT,
                'test',
                array('exception' => new \Exception())
            ],
            [true, OutputInterface::VERBOSITY_QUIET, LogLevel::ALERT, 'test', []],
            [true, OutputInterface::VERBOSITY_NORMAL, LogLevel::ALERT, 'test', []],
            [true, OutputInterface::VERBOSITY_VERBOSE, LogLevel::ALERT, 'test', []],
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
            [true, OutputInterface::VERBOSITY_NORMAL, LogLevel::WARNING, 'test', []],
            [true, OutputInterface::VERBOSITY_VERBOSE, LogLevel::WARNING, 'test', []],
            [true, OutputInterface::VERBOSITY_VERY_VERBOSE, LogLevel::WARNING, 'test', []],
            [true, OutputInterface::VERBOSITY_DEBUG, LogLevel::WARNING, 'test', []],

            [false, OutputInterface::VERBOSITY_QUIET, LogLevel::NOTICE, 'test', []],
            [true, OutputInterface::VERBOSITY_NORMAL, LogLevel::NOTICE, 'test', []],
            [true, OutputInterface::VERBOSITY_VERBOSE, LogLevel::NOTICE, 'test', []],
            [true, OutputInterface::VERBOSITY_VERY_VERBOSE, LogLevel::NOTICE, 'test', []],
            [true, OutputInterface::VERBOSITY_DEBUG, LogLevel::NOTICE, 'test', []],

            [false, OutputInterface::VERBOSITY_QUIET, LogLevel::INFO, 'test', []],
            [false, OutputInterface::VERBOSITY_NORMAL, LogLevel::INFO, 'test', []],
            [true, OutputInterface::VERBOSITY_VERBOSE, LogLevel::INFO, 'test', []],
            [true, OutputInterface::VERBOSITY_VERY_VERBOSE, LogLevel::INFO, 'test', []],
            [true, OutputInterface::VERBOSITY_DEBUG, LogLevel::INFO, 'test', []],

            [false, OutputInterface::VERBOSITY_QUIET, LogLevel::DEBUG, 'test', []],
            [false, OutputInterface::VERBOSITY_NORMAL, LogLevel::DEBUG, 'test', []],
            [false, OutputInterface::VERBOSITY_VERBOSE, LogLevel::DEBUG, 'test', []],
            [false, OutputInterface::VERBOSITY_VERY_VERBOSE, LogLevel::DEBUG, 'test', []],
            [true, OutputInterface::VERBOSITY_DEBUG, LogLevel::DEBUG, 'test', []],
        ];
    }
}
