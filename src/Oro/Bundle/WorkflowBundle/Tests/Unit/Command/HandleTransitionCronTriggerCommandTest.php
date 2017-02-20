<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

use Oro\Bundle\CronBundle\Command\ActiveCronCommandInterface;
use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;

class HandleTransitionCronTriggerCommandTest extends \PHPUnit_Framework_TestCase
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

    public function testCommandImplementsProperInterface()
    {
        $this->assertInstanceOf(ActiveCronCommandInterface::class, $this->command);
        $this->assertNotInstanceOf(CronCommandInterface::class, $this->command);
    }

    public function testIsActive()
    {
        $this->assertTrue($this->command->isActive());
    }
}
