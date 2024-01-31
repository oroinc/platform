<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @group regression
 */
class HelpViewOptionForDumpApiDocCommands extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function assertOutputContainsViewOption(string $output): void
    {
        self::assertStringContainsString('--view[=VIEW]', $output);
        self::assertStringContainsString('A view for which API definitions should be dumped.', $output);
    }

    public function testHelpOptionForApiDocDumpCommand(): void
    {
        $output = self::runCommand('api:doc:dump', ['--help']);
        $this->assertOutputContainsViewOption($output);
    }

    public function testHelpCommandForApiDocDumpCommand(): void
    {
        $output = self::runCommand('help', ['api:doc:dump']);
        $this->assertOutputContainsViewOption($output);
    }

    public function testHelpOptionForApiSwaggerDumpCommand(): void
    {
        $output = self::runCommand('api:swagger:dump', ['--help']);
        $this->assertOutputContainsViewOption($output);
    }

    public function testHelpCommandForApiSwaggerDumpCommand(): void
    {
        $output = self::runCommand('help', ['api:swagger:dump']);
        $this->assertOutputContainsViewOption($output);
    }
}
