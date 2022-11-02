<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Validator\Constraints\TransitionIsAllowed;
use Oro\Bundle\WorkflowBundle\Validator\Constraints\TransitionIsAllowedValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class TransitionIsAllowedValidatorTest extends ConstraintValidatorTestCase
{
    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(WorkflowRegistry::class);

        parent::setUp();
    }

    protected function createValidator()
    {
        return new TransitionIsAllowedValidator($this->registry);
    }

    private function getWorkflowStep(string $name): WorkflowStep
    {
        $step = new WorkflowStep();
        $step->setName($name);

        return $step;
    }

    private function getWorkflowItem(string $workflowName, WorkflowStep $currentStep): WorkflowItem
    {
        $item = $this->createMock(WorkflowItem::class);
        $item->expects($this->any())
            ->method('getWorkflowName')
            ->willReturn($workflowName);
        $item->expects($this->any())
            ->method('getCurrentStep')
            ->willReturn($currentStep);

        return $item;
    }

    public function testValidateWhenTransitionAllowed()
    {
        $workflowName = 'test_workflow';
        $transitionName = 'test_transition';

        $currentStep = $this->getWorkflowStep('test_step');
        $workflowItem = $this->getWorkflowItem($workflowName, $currentStep);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('isTransitionAllowed')
            ->with($workflowItem, $transitionName, $this->isInstanceOf(Collection::class), true)
            ->willReturn(true);

        $this->registry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);

        $value = new WorkflowData();

        $constraint = new TransitionIsAllowed($workflowItem, $transitionName);
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider validateExceptionsDataProvider
     */
    public function testValidateExceptions(
        \Exception $workflowException,
        string $expectedMessage,
        array $expectedMessageParameters
    ) {
        $workflowName = 'test_workflow';
        $transitionName = 'test_transition';

        $currentStep = $this->getWorkflowStep('test_step');
        $workflowItem = $this->getWorkflowItem($workflowName, $currentStep);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())
            ->method('isTransitionAllowed')
            ->with($workflowItem, $transitionName, $this->isInstanceOf(Collection::class), true)
            ->willThrowException($workflowException);

        $this->registry->expects($this->once())
            ->method('getWorkflow')
            ->with($workflowName)
            ->willReturn($workflow);

        $value = new WorkflowData();

        $constraint = new TransitionIsAllowed($workflowItem, $transitionName);
        $this->validator->validate($value, $constraint);

        $this->buildViolation($expectedMessage)
            ->setParameters($expectedMessageParameters)
            ->assertRaised();
    }

    public function validateExceptionsDataProvider(): array
    {
        $constraint = $this->createMock(TransitionIsAllowed::class);

        return [
            [
                'workflowException' => InvalidTransitionException::unknownTransition('test_transition'),
                'expectedMessage' => $constraint->unknownTransitionMessage,
                'expectedMessageParameters' => ['{{ transition }}' => 'test_transition']
            ],
            [
                'workflowException' => InvalidTransitionException::notStartTransition(
                    'test_workflow',
                    'test_transition'
                ),
                'expectedMessage' => $constraint->notStartTransitionMessage,
                'expectedMessageParameters' => ['{{ transition }}' => 'test_transition']
            ],
            [
                'workflowException' => InvalidTransitionException::stepHasNoAllowedTransition(
                    'test_workflow',
                    'test_step',
                    'test_transition'
                ),
                'expectedMessage' => $constraint->stepHasNotAllowedTransitionMessage,
                'expectedMessageParameters' => ['{{ transition }}' => 'test_transition', '{{ step }}' => 'test_step']
            ],
            [
                'workflowException' => new InvalidTransitionException(),
                'expectedMessage' => $constraint->someConditionsNotMetMessage,
                'expectedMessageParameters' => []
            ],
        ];
    }
}
