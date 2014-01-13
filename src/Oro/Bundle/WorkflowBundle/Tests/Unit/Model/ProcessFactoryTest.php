<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\ProcessFactory;

class ProcessFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $actionAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Action\ActionAssembler')
            ->disableOriginalConstructor()
            ->getMock();
        $processDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition')
            ->disableOriginalConstructor()
            ->getMock();

        $processFactory = new ProcessFactory($actionAssembler);
        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\Process',
            $processFactory->create($processDefinition)
        );
    }
}
