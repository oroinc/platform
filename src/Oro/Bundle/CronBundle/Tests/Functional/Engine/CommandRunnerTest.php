<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Engine;

use Oro\Bundle\CronBundle\Engine\CommandRunner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CommandRunnerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
    }

    public function testShouldAllowToTakeFromContainerAsService()
    {
        $runner = $this->getContainer()->get('oro_cron.engine.command_runner');

        self::assertInstanceOf(CommandRunner::class, $runner);
    }

    public function testShouldRunCommandAndReturnOutput()
    {
        /** @var CommandRunner $runner */
        $runner = $this->getContainer()->get('oro_cron.engine.command_runner');

        $result = $runner->run('debug:router');

        $this->assertContains('Name', $result);
        $this->assertContains('Method', $result);
        $this->assertContains('Scheme', $result);
        $this->assertContains('Host', $result);
        $this->assertContains('Path', $result);
    }

    public function testShouldAcceptCommandArguments()
    {
        /** @var CommandRunner $runner */
        $runner = $this->getContainer()->get('oro_cron.engine.command_runner');

        $result = $runner->run('debug:router', ['--help']);

        $this->assertNotContains('Name', $result);
        $this->assertNotContains('Method', $result);
        $this->assertNotContains('Scheme', $result);
        $this->assertNotContains('Host', $result);
        $this->assertNotContains('Path', $result);
        $this->assertContains('Help:', $result);
        $this->assertContains('The debug:router displays the configured routes:', $result);
    }
}
