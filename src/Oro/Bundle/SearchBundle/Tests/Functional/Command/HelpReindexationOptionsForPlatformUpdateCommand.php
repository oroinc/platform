<?php

namespace Oro\Bundle\SearchBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class HelpReindexationOptionsForPlatformUpdateCommand extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    private function assertOutputContainsReindexationOptions(string $output)
    {
        self::assertContains('--skip-search-reindexation', $output);
        self::assertContains(
            'Determines whether search data reindexation need to be triggered or not',
            $output
        );
        self::assertContains('--schedule-search-reindexation', $output);
        self::assertContains(
            'Determines whether search data reindexation need to be scheduled or not',
            $output
        );
    }

    public function testHelpOptionForOroPlatformUpdateCommand()
    {
        $output = self::runCommand('oro:platform:update', ['--help']);
        $this->assertOutputContainsReindexationOptions($output);
    }

    public function testHelpCommandForOroPlatformUpdateCommand()
    {
        $output = self::runCommand('help', ['oro:platform:update']);
        $this->assertOutputContainsReindexationOptions($output);
    }
}
