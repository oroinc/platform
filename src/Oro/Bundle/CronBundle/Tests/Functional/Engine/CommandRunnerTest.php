<?php

namespace Oro\Bundle\CronBundle\Tests\Functional\Engine;

use Oro\Bundle\CronBundle\Engine\CommandRunner;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CommandRunnerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testShouldAllowToTakeFromContainerAsService(): void
    {
        $runner = self::getContainer()->get('oro_cron.engine.command_runner');

        self::assertInstanceOf(CommandRunner::class, $runner);
    }

    public function testShouldRunCommandAndReturnOutput(): void
    {
        $runner = self::getContainer()->get('oro_cron.engine.command_runner');

        $result = $runner->run('about');

        self::assertStringContainsString('Symfony', $result);
        self::assertStringContainsString('Kernel', $result);
        self::assertStringContainsString('PHP', $result);
    }

    public function testShouldAcceptCommandArguments(): void
    {
        $runner = self::getContainer()->get('oro_cron.engine.command_runner');

        $result = $runner->run('about', ['--help']);

        self::assertStringContainsString('Help:', $result);
        self::assertStringContainsString('Display information about the current project', $result);
    }
}
