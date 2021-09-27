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

        static::assertStringContainsString('Symfony', $result);
        static::assertStringContainsString('Kernel', $result);
        static::assertStringContainsString('PHP', $result);
    }

    public function testShouldAcceptCommandArguments(): void
    {
        $runner = self::getContainer()->get('oro_cron.engine.command_runner');

        $result = $runner->run('about', ['--help']);

        static::assertStringContainsString('Help:', $result);
        static::assertStringContainsString('Display information about the current project', $result);
    }
}
