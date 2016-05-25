<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Generator;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Generator\TriggerScheduleOptionsVerifier;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\TransitionScheduleHelper;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface;

class TriggerScheduleOptionsVerifierTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowAssembler|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowAssembler;

    /** @var TransitionScheduleHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $transitionScheduleHelper;

    /** @var TriggerScheduleOptionsVerifier */
    protected $verifier;

    /** @var WorkflowDefinition|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowDefinition;

    protected function setUp()
    {
        $this->workflowAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler')
            ->disableOriginalConstructor()->getMock();
        $this->transitionScheduleHelper = $this->getMockBuilder(
            'Oro\Bundle\WorkflowBundle\Model\TransitionScheduleHelper'
        )->disableOriginalConstructor()->getMock();

        $this->workflowDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()->getMock();

        $this->verifier = new TriggerScheduleOptionsVerifier(
            $this->workflowAssembler,
            $this->transitionScheduleHelper
        );
    }

    public function testVerify()
    {
        /** @var ExpressionVerifierInterface|\PHPUnit_Framework_MockObject_MockObject $expressionVerifier */
        $expressionVerifier = $this->getMock(
            'Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface'
        );
        $expressionVerifier->expects($this->once())->method('verify');
        $this->verifier->addOptionVerifier('cron', $expressionVerifier);
        $this->verifier->verify(['cron' => 'expression value'], $this->workflowDefinition, 'transition_name');
    }

    public function testVerifyExceptions()
    {
        $this->setExpectedException('InvalidArgumentException', 'Option "cron" is REQUIRED for transition schedule.');
        $this->verifier->verify([], $this->workflowDefinition, 'transition_name');
    }

    public function testPrepareFilterExpression()
    {
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();

        $this->workflowAssembler->expects($this->once())
            ->method('assemble')->with($this->workflowDefinition, false)
            ->willReturn($workflow);

        $stepsManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\StepManager')->getMock();

        $workflow->expects($this->once())
            ->method('getStepManager')
            ->willReturn($stepsManager);

        $step = new Step();
        $step->setName('step1');
        $stepsManager->expects($this->once())
            ->method('getRelatedTransitionSteps')
            ->with('transitionName')
            ->willReturn([$step]);

        $this->workflowDefinition->expects($this->once())->method('getRelatedEntity')->willReturn('EntityClass');

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(['getDQL'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->transitionScheduleHelper->expects($this->once())
            ->method('createQuery')
            ->with(['step1'], 'EntityClass', 'filterDQL')
            ->willReturn($query);

        $query->expects($this->once())->method('getDQL')->willReturn('preparedDql');

        /** @var ExpressionVerifierInterface|\PHPUnit_Framework_MockObject_MockObject $expressionVerifier */
        $expressionVerifier = $this->getMock(
            'Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface'
        );

        $this->verifier->addOptionVerifier('filter', $expressionVerifier);
        $expressionVerifier->expects($this->once())->method('verify')->with('preparedDql');

        $this->verifier->verify(['cron' => '', 'filter' => 'filterDQL'], $this->workflowDefinition, 'transitionName');
    }
}
