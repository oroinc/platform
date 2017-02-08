<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Async;

use Oro\Bundle\CronBundle\Async\CommandRunnerMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CommandRunnerMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testShouldAllowToTakeFromContainerAsService()
    {
        $runner = $this->getContainer()->get('oro_cron.async.command_runner_message_processor');

        self::assertInstanceOf(CommandRunnerMessageProcessor::class, $runner);
    }
}
