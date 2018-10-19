<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;

class HandleTransitionCronTriggerCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var HandleProcessTriggerCommand */
    private $command;


    protected function setUp()
    {
        $this->command = new HandleProcessTriggerCommand();
    }

    protected function tearDown()
    {
        unset($this->command);
    }

    public function testConfigure()
    {
        $this->command->configure();

        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getName());
    }
}
