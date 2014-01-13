<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\Process;

class ProcessTest extends \PHPUnit_Framework_TestCase
{
    public function testExecute()
    {
        $context = array('context');
        $configuration = array('config');

        $action = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Action\ActionInterface')
            ->getMock();
        $action->expects($this->exactly(2))
            ->method('execute')
            ->with($context);

        $processDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $processDefinition->expects($this->once())
            ->method('getActionsConfiguration')
            ->will($this->returnValue($configuration));

        $actionAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Action\ActionAssembler')
            ->disableOriginalConstructor()
            ->getMock();
        $actionAssembler->expects($this->once())
            ->method('assemble')
            ->will($this->returnValue($action));

        $process = new Process($actionAssembler, $processDefinition);
        $process->execute($context);
        $process->execute($context);
    }
}
