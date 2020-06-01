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

        static::assertStringContainsString('Name', $result);
        static::assertStringContainsString('Method', $result);
        static::assertStringContainsString('Scheme', $result);
        static::assertStringContainsString('Host', $result);
        static::assertStringContainsString('Path', $result);
    }

    public function testShouldAcceptCommandArguments()
    {
        /** @var CommandRunner $runner */
        $runner = $this->getContainer()->get('oro_cron.engine.command_runner');

        $result = $runner->run('debug:router', ['--help']);

        static::assertStringNotContainsString('Name', $result);
        static::assertStringNotContainsString('Method', $result);
        static::assertStringNotContainsString('Scheme', $result);
        static::assertStringNotContainsString('Host', $result);
        static::assertStringNotContainsString('Path', $result);
        static::assertStringContainsString('Help:', $result);
        static::assertStringContainsString('The debug:router displays the configured routes:', $result);
    }
}
