<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionSchedule;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\EntityConnector;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\TransitionQueryFactory;
use Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\TriggerScheduleOptionsVerifier;
use Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler;
use Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface;

class TriggerScheduleOptionsVerifierTest extends \PHPUnit_Framework_TestCase
{
    /** @var WorkflowAssembler|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowAssembler;

    /** @var \Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\TriggerScheduleOptionsVerifier */
    protected $verifier;

    /** @var WorkflowDefinition|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowDefinition;

    /** @var  TransitionQueryFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $queryFactory;

    /** @var  EntityConnector|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityConnector;

    protected function setUp()
    {
        $this->workflowAssembler = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryFactory = $this->getMockBuilder(
            'Oro\Bundle\WorkflowBundle\Model\TransitionSchedule\TransitionQueryFactory'
        )->disableOriginalConstructor()->getMock();

        $this->workflowDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityConnector = $this->getMock('Oro\Bundle\WorkflowBundle\Model\EntityConnector');


        $this->verifier = new TriggerScheduleOptionsVerifier(
            $this->workflowAssembler,
            $this->queryFactory,
            $this->entityConnector
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Option "cron" is required for transition schedule.
     */
    public function testVerifyExceptions()
    {
        $this->verifier->verify([], $this->workflowDefinition, 'transition_name');
    }

    /**
     * @dataProvider prepareFilterExpressionDataProvider
     * @param bool $isEntityWorkflowAware
     */
    public function testPrepareFilterExpression($isEntityWorkflowAware)
    {
        $step = new Step();
        $step->setName('step1');

        $stepsManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\StepManager')
            ->disableOriginalConstructor()
            ->getMock();
        $stepsManager->expects($this->exactly((int) $isEntityWorkflowAware))
            ->method('getRelatedTransitionSteps')
            ->with('transitionName')
            ->willReturn([$step]);

        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $workflow->expects($this->exactly((int) $isEntityWorkflowAware))
            ->method('getStepManager')
            ->willReturn($stepsManager);

        $this->workflowAssembler->expects($this->once())
            ->method('assemble')
            ->with($this->workflowDefinition, false)
            ->willReturn($workflow);

        $this->workflowDefinition->expects($this->once())->method('getRelatedEntity')->willReturn('EntityClass');

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->setMethods(['getDQL'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->queryFactory->expects($this->exactly((int) $isEntityWorkflowAware))
            ->method('create')
            ->with(['step1'], 'EntityClass', 'filterDQL')
            ->willReturn($query);

        /** @var ExpressionVerifierInterface|\PHPUnit_Framework_MockObject_MockObject $expressionVerifier */
        $expressionVerifier = $this->getMock(
            'Oro\Bundle\WorkflowBundle\Validator\Expression\ExpressionVerifierInterface'
        );
        $expressionVerifier->expects($this->exactly((int) $isEntityWorkflowAware))->method('verify')->with($query);

        $this->entityConnector->expects($this->once())->method('isWorkflowAware')->willReturn($isEntityWorkflowAware);

        $this->verifier->addOptionVerifier('filter', $expressionVerifier);
        $this->verifier->verify(['cron' => '', 'filter' => 'filterDQL'], $this->workflowDefinition, 'transitionName');
    }

    /**
     * @return array
     */
    public function prepareFilterExpressionDataProvider()
    {
        return [
            'workflow aware' => [
                'isEntityWorkflowAware' => true,
            ],
            'not workflow aware' => [
                'isEntityWorkflowAware' => false,
            ],
        ];
    }
}
