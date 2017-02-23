<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Command;

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

    /**
     * @deprecated Since 2.0.3. Will be removed in 2.1. Must be refactored at BAP-13973
     *
     * This method never used because all schedules for workflow transition triggers must
     * defined within YML configuration
     *
     * @return string
     */
    public function testGetDefaultDefinition()
    {
        $this->assertEquals('*/1 * * * *', $this->command->getDefaultDefinition());
    }

    /**
     * @deprecated Since 2.0.3. Will be removed in 2.1. Must be refactored at BAP-13973
     *
     * This command cannot be disabled
     *
     * @return bool
     */
    public function testIsActive()
    {
        $this->assertTrue($this->command->isActive());
    }
}
