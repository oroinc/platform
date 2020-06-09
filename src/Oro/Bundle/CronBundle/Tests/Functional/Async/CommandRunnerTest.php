<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Async;

use Oro\Bundle\CronBundle\Async\CommandRunner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CommandRunnerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testShouldAllowToTakeFromContainerAsService()
    {
        $runner = $this->getContainer()->get('oro_cron.async.command_runner');

        self::assertInstanceOf(CommandRunner::class, $runner);
    }
}
