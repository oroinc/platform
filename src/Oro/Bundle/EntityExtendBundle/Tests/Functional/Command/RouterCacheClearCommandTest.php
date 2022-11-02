<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Command;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Command\CommandTestingTrait;

class RouterCacheClearCommandTest extends WebTestCase
{
    use CommandTestingTrait;

    public function testExecute()
    {
        $commandTester = $this->doExecuteCommand('router:cache:clear');

        $this->assertSuccessReturnCode($commandTester);
        $this->assertOutputContains($commandTester, 'Clearing the routing cache');
        $this->assertOutputContains($commandTester, 'The cache was successfully cleared.');
    }
}
