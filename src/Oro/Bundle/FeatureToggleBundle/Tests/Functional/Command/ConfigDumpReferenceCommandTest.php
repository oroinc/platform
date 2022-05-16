<?php

namespace Oro\Bundle\FeatureToggleBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;

class ConfigDumpReferenceCommandTest extends WebTestCase
{
    use CommandTestingTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testExecute(): void
    {
        $commandTester = $this->doExecuteCommand('oro:feature-toggle:config:dump-reference');
        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, '# The structure of "Resources/config/oro/features.yml"');
    }
}
