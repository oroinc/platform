<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Exception\UnsupportedButtonException;
use Oro\Bundle\WorkflowBundle\Button\TransitionButton;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

abstract class TransitionButtonProviderExtensionTestCase extends AbstractTransitionButtonProviderExtensionTestCase
{
    private const DATAGRID_NAME = 'datagrid1';
    private const ENTITY = 'entity1';

    /**
     * @dataProvider buttonDataProvider
     */
    public function testIsSupport(ButtonInterface $button, bool $expected)
    {
        $this->assertSame($expected, $this->extension->supports($button));
    }

    public function buttonDataProvider(): array
    {
        $transition = $this->createMock(Transition::class);

        $createButtonInstance = function ($className, $isStart) use ($transition) {
            $cloneTransition = clone $transition;
            $cloneTransition->expects($this->once())
                ->method('isStart')
                ->willReturn($isStart);

            $button = $this->createMock($className);
            $button->expects($this->once())
                ->method('getTransition')
                ->willReturn($cloneTransition);

            return $button;
        };

        return [
            'startTransitionButton' => [
                'button' => $createButtonInstance(TransitionButton::class, true),
                'expected' => false
            ],
            'unSupportedButton' => [
                'button' => $this->createMock(ButtonInterface::class),
                'expected' => false
            ],
            'validTransitionButton' => [
                'button' => $createButtonInstance(TransitionButton::class, false),
                'expected' => true
            ],
        ];
    }

    /**
     * @dataProvider findDataProvider
     */
    public function testFind(bool $expected, string $entityClass = null, string $datagrid = null)
    {
        $this->applicationProvider->expects($this->atLeastOnce())
            ->method('getCurrentApplication')
            ->willReturn($expected ? $this->getApplication() : null);

        $buttons = [];

        if ($expected) {
            $transition = $this->createMock(Transition::class);

            $transitionManager = $this->getTransitionManager([$transition], 'getTransitions');

            $workflow = $this->getWorkflow($transitionManager);

            $this->workflowRegistry->expects($this->once())
                ->method('getActiveWorkflows')
                ->willReturn(new ArrayCollection([$workflow]));

            $buttonContext = (new ButtonContext())
                ->setEntity($entityClass)
                ->setDatagridName($datagrid);
            $buttons = [new TransitionButton($transition, $workflow, $buttonContext)];
        }

        $this->assertEquals(
            $buttons,
            $this->extension->find(
                (new ButtonSearchContext())->setEntity($entityClass)->setDatagrid($datagrid)
            )
        );
    }

    public function findDataProvider(): array
    {
        return [
            'not matched entity' => [
                'expected' => false,
                'entityClass' => 'class',
            ],
            'matched datagrid, not matched entity' => [
                'expected' => false,
                'entityClass' => 'test_entity',
                'datagrid' => self::DATAGRID_NAME
            ],
            'matched' => [
                'expected' => true,
                'entityClass' => self::ENTITY,
                'datagrid' => self::DATAGRID_NAME
            ]
        ];
    }

    public function testIsAvailableWhenButtonNotSupported()
    {
        $this->expectException(UnsupportedButtonException::class);
        $this->extension->isAvailable(
            $this->createMock(ButtonInterface::class),
            $this->createMock(ButtonSearchContext::class)
        );
    }

    /**
     * @dataProvider isAvailableDataProvider
     */
    public function testIsAvailable(bool $expected, ButtonInterface $button)
    {
        $this->assertEquals($expected, $this->extension->isAvailable($button, new ButtonSearchContext()));
    }

    public function isAvailableDataProvider(): array
    {
        return [
            'workflow item not exist' => [
                'expected' => false,
                'button' => $this->createTransitionButton(true, false),
            ],
            'transition is not available and not exist workflow item' => [
                'expected' => false,
                'button' => $this->createTransitionButton(false, false),
            ],
            'transition is not available and exist workflow item' => [
                'expected' => false,
                'button' => $this->createTransitionButton(false),
            ],
            'transition is hidden' => [
                'expected' => false,
                'button' => $this->createTransitionButton(true, true, true),
            ],
            'transition forbidden by allowed_transitions' => [
                'expected' => false,
                'button' => $this->createTransitionButton(true, true, false, false),
            ],
            'transition is enable' => [
                'expected' => true,
                'button' => $this->createTransitionButton(true),
            ],
        ];
    }

    private function getWorkflow(TransitionManager $transitionManager): Workflow
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->onlyMethods(['getTransitionManager'])
            ->disableOriginalConstructor()
            ->getMock();

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())
            ->method('getDatagrids')
            ->willReturn([self::DATAGRID_NAME]);
        $definition->expects($this->any())
            ->method('getRelatedEntity')
            ->willReturn(self::ENTITY);

        $workflow->setDefinition($definition);

        $workflow->expects($this->any())
            ->method('getTransitionManager')
            ->willReturn($transitionManager);

        return $workflow;
    }

    private function createTransitionButton(
        bool $isAvailable,
        bool $isExistWorkflowItem = true,
        bool $isHidden = false,
        bool $isAllowedTransition = true
    ): TransitionButton {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())
            ->method('isStart')
            ->willReturn(false);
        $transition->expects($this->any())
            ->method('isHidden')
            ->willReturn($isHidden);

        $step = $this->createMock(Step::class);
        $step->expects($this->any())
            ->method('isAllowedTransition')
            ->willReturn($isAllowedTransition);
        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects($this->any())
            ->method('getStep')
            ->willReturn($step);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())
            ->method('isTransitionAvailable')
            ->willReturn($isAvailable);
        $workflow->expects($this->any())
            ->method('getStepManager')
            ->willReturn($stepManager);

        if (true === $isExistWorkflowItem) {
            $workflowItem = $this->createMock(WorkflowItem::class);
            $workflowItem->expects($this->any())
                ->method('getCurrentStep')
                ->willReturn((new WorkflowStep())->setName('test_step'));

            $workflow->expects($this->once())
                ->method('getWorkflowItemByEntityId')
                ->willReturn($workflowItem);
        }

        $button = $this->createMock(TransitionButton::class);
        $button->expects($this->any())
            ->method('getWorkflow')
            ->willReturn($workflow);
        $button->expects($this->any())
            ->method('getTransition')
            ->willReturn($transition);

        return $button;
    }
}
