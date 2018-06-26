<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Model\ProcessFactory;
use Oro\Component\Action\Action\ActionAssembler;
use Oro\Component\ConfigExpression\ExpressionFactory;

class ProcessFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ActionAssembler $actionAssembler */
        $actionAssembler = $this->getMockBuilder('Oro\Component\Action\Action\ActionAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit\Framework\MockObject\MockObject|ProcessDefinition $processDefinition */
        $processDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit\Framework\MockObject\MockObject|ExpressionFactory $conditionFactory */
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
