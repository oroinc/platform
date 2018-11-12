<?php

namespace Oro\Bundle\CronBundle\Tests\Unit\Async;

use Oro\Bundle\CronBundle\Async\CommandRunner;
use Oro\Bundle\CronBundle\Async\Topics;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class CommandRunnerTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldBeConstructedWithAllRequiredArguments()
    {
        new  CommandRunner($this->createProducerMock());
    }

    public function testShouldSendMessageWithCommandParams()
    {
        $testCommandName = 'oro:test';
        $testCommandArguments = ['argument' => 'value'];

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(
                Topics::RUN_COMMAND,
                [
                    'command' => $testCommandName,
                    'arguments' => $testCommandArguments
                ]
            )
        ;

        $runner = new CommandRunner($producer);
        $runner->run($testCommandName, $testCommandArguments);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject | MessageProducerInterface
     */
    private function createProducerMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }
}
