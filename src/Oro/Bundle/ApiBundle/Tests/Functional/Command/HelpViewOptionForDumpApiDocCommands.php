<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class HelpViewOptionForDumpApiDocCommands extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function assertOutputContainsViewOption(string $output)
    {
        self::assertContains('--view[=VIEW]', $output);
        self::assertContains('A view for which API definitions should be dumped.', $output);
    }

    public function testHelpOptionForApiDocDumpCommand()
    {
        $output = self::runCommand('api:doc:dump', ['--help']);
        $this->assertOutputContainsViewOption($output);
    }

    public function testHelpCommandForApiDocDumpCommand()
    {
        $output = self::runCommand('help', ['api:doc:dump']);
        $this->assertOutputContainsViewOption($output);
    }

    public function testHelpOptionForApiSwaggerDumpCommand()
    {
        $output = self::runCommand('api:swagger:dump', ['--help']);
        $this->assertOutputContainsViewOption($output);
    }

    public function testHelpCommandForApiSwaggerDumpCommand()
    {
        $output = self::runCommand('help', ['api:swagger:dump']);
        $this->assertOutputContainsViewOption($output);
    }
}
