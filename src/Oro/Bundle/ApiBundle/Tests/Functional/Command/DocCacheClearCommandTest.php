<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;

/**
 * @group regression
 */
class DocCacheClearCommandTest extends WebTestCase
{
    use CommandTestingTrait;

    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testApiDocCacheClear(): void
    {
        $commandTester = $this->doExecuteCommand('oro:api:doc:cache:clear');
        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, '[OK] API documentation cache was successfully cleared');
    }
}
