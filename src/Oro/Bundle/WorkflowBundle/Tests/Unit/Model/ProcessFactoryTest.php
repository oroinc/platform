<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Model\ProcessFactory;
use Oro\Bundle\WorkflowBundle\Model\Action\ActionAssembler;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Component\ConfigExpression\ExpressionFactory;

class ProcessFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionAssembler $actionAssembler */
        $actionAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Action\ActionAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ProcessDefinition $processDefinition */
        $processDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ExpressionFactory $conditionFactory */
        $conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $processFactory = new ProcessFactory($actionAssembler, $conditionFactory);
        $this->assertInstanceOf(
            'Oro\Bundle\WorkflowBundle\Model\Process',
            $processFactory->create($processDefinition)
        );
    }
}
