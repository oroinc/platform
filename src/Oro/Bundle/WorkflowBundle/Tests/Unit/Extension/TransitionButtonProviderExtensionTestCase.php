<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
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
    const DATAGRID_NAME = 'datagrid1';
    const ENTITY = 'entity1';

    /**
     * @dataProvider buttonDataProvider
     *
     * @param ButtonInterface $button
     * @param bool $expected
     */
    public function testIsSupport($button, $expected)
    {
        $this->assertSame($expected, $this->extension->supports($button));
    }

    /**
     * @return array
     */
    public function buttonDataProvider()
    {
        $transition = $this->createMock(Transition::class);

        $createButtonInstance = function ($className, $isStart) use ($transition) {
            $cloneTransition = clone $transition;
            $cloneTransition->expects($this->once())->method('isStart')->willReturn($isStart);

            $mockButton = $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
            $mockButton->expects($this->once())->method('getTransition')->willReturn($cloneTransition);

            return $mockButton;
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
     *
     * @param bool $expected
     * @param null $entityClass
     * @param null $datagrid
     */
    public function testFind($expected, $entityClass = null, $datagrid = null)
    {
        $this->applicationProvider->expects($this->atLeastOnce())
            ->method('getCurrentApplication')
            ->willReturn($expected ? $this->getApplication() : null);

        $buttons = [];

        if ($expected) {
            /** @var Transition|\PHPUnit\Framework\MockObject\MockObject $transition */
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

    /**
     * @return array
     */
    public function findDataProvider()
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

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\UnsupportedButtonException
     */
    public function testIsAvailableWhenButtonNotSupported()
    {
        $this->extension->isAvailable(
            $this->createMock(ButtonInterface::class),
            $this->createMock(ButtonSearchContext::class)
        );
    }

    /**
     * @dataProvider isAvailableDataProvider
     *
     * @param bool $expected
     * @param ButtonInterface $button
     */
    public function testIsAvailable($expected, ButtonInterface $button)
    {
        $this->assertEquals($expected, $this->extension->isAvailable($button, new ButtonSearchContext()));
    }

    /**
     * @return array
     */
    public function isAvailableDataProvider()
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

    /**
     * @param TransitionManager|\PHPUnit\Framework\MockObject\MockObject $transitionManager
     * @return Workflow|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getWorkflow(TransitionManager $transitionManager)
    {
        /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject $workflow */
        $workflow = $this->getMockBuilder(Workflow::class)
            ->setMethods(['getTransitionManager'])
            ->disableOriginalConstructor()
            ->getMock();

        $definition = $this->createMock(WorkflowDefinition::class);
        $definition->expects($this->any())->method('getDatagrids')->willReturn([self::DATAGRID_NAME]);
        $definition->expects($this->any())->method('getRelatedEntity')->willReturn(self::ENTITY);

        $workflow->setDefinition($definition);

        $workflow->expects($this->any())->method('getTransitionManager')->willReturn($transitionManager);

        return $workflow;
    }


    /**
     * @param $isAvailable
     * @param bool $isExistWorkflowItem
     * @param bool $isHidden
     * @param bool $isAllowedTransition
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|TransitionButton
     */
    protected function createTransitionButton(
        $isAvailable,
        $isExistWorkflowItem = true,
        $isHidden = false,
        $isAllowedTransition = true
    ) {
        $transition = $this->createMock(Transition::class);
        $transition->expects($this->any())
            ->method('isStart')->willReturn(false);
        $transition->expects($this->any())
            ->method('isHidden')->willReturn($isHidden);

        $step = $this->createMock(Step::class);
        $step->expects($this->any())->method('isAllowedTransition')
            ->willReturn($isAllowedTransition);
        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects($this->any())->method('getStep')
            ->willReturn($step);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())->method('isTransitionAvailable')
            ->willReturn($isAvailable);
        $workflow->expects($this->any())->method('getStepManager')
            ->willReturn($stepManager);



        if (true === $isExistWorkflowItem) {
            $workflowItem = $this->createMock(WorkflowItem::class);
            $workflowItem->expects($this->any())->method('getCurrentStep')
                ->willReturn(
                    (new WorkflowStep())->setName('test_step')
                );

            $workflow->expects($this->once())
                ->method('getWorkflowItemByEntityId')
                ->willReturn($workflowItem);
        }

        $button = $this->getMockBuilder(TransitionButton::class)
            ->disableOriginalConstructor()->getMock();
        $button->expects($this->any())->method('getWorkflow')->willReturn($workflow);
        $button->expects($this->any())->method('getTransition')->willReturn($transition);

        return $button;
    }
}
