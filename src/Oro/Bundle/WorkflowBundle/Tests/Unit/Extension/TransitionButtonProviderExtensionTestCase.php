<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Exception\UnsupportedButtonException;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\ActionBundle\Provider\OriginalUrlProvider;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Oro\Bundle\WorkflowBundle\Button\TransitionButton;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Extension\AbstractButtonProviderExtension;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\StepManager;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class TransitionButtonProviderExtensionTestCase extends TestCase
{
    private const DATAGRID_NAME = 'datagrid1';
    private const ENTITY = 'entity1';

    protected WorkflowRegistry&MockObject $workflowRegistry;
    protected RouteProviderInterface&MockObject $routeProvider;
    protected OriginalUrlProvider&MockObject $originalUrlProvider;
    protected CurrentApplicationProviderInterface&MockObject $applicationProvider;
    protected AbstractButtonProviderExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->routeProvider = $this->createMock(RouteProviderInterface::class);
        $this->originalUrlProvider = $this->createMock(OriginalUrlProvider::class);
        $this->applicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);

        $this->extension = $this->createExtension();
        $this->extension->setApplicationProvider($this->applicationProvider);
    }

    abstract protected function createExtension(): AbstractButtonProviderExtension;

    abstract protected function getApplication(): string;

    private function getTransitionManager(array $transitions, string $method): TransitionManager
    {
        $manager = $this->createMock(TransitionManager::class);
        $manager->expects(self::any())
            ->method($method)
            ->willReturn(new ArrayCollection($transitions));

        return $manager;
    }

    /**
     * @dataProvider buttonDataProvider
     */
    public function testIsSupport(ButtonInterface $button, bool $expected): void
    {
        self::assertSame($expected, $this->extension->supports($button));
    }

    public function buttonDataProvider(): array
    {
        $transition = $this->createMock(Transition::class);

        $createButtonInstance = function ($className, $isStart) use ($transition) {
            $cloneTransition = clone $transition;
            $cloneTransition->expects(self::once())
                ->method('isStart')
                ->willReturn($isStart);

            $button = $this->createMock($className);
            $button->expects(self::once())
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
    public function testFind(bool $expected, ?string $entityClass = null, ?string $datagrid = null): void
    {
        $this->applicationProvider->expects(self::atLeastOnce())
            ->method('getCurrentApplication')
            ->willReturn($expected ? $this->getApplication() : null);

        $buttons = [];

        if ($expected) {
            $transition = $this->createMock(Transition::class);

            $transitionManager = $this->getTransitionManager([$transition], 'getTransitions');

            $workflow = $this->getWorkflow($transitionManager);

            $this->workflowRegistry->expects(self::once())
                ->method('getActiveWorkflows')
                ->willReturn(new ArrayCollection([$workflow]));

            $buttonContext = (new ButtonContext())
                ->setEntity($entityClass)
                ->setDatagridName($datagrid);
            $buttons = [new TransitionButton($transition, $workflow, $buttonContext)];
        }

        self::assertEquals(
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

    public function testIsAvailableWhenButtonNotSupported(): void
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
    public function testIsAvailable(bool $expected, ButtonInterface $button): void
    {
        self::assertEquals($expected, $this->extension->isAvailable($button, new ButtonSearchContext()));
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
        $definition->expects(self::any())
            ->method('getDatagrids')
            ->willReturn([self::DATAGRID_NAME]);
        $definition->expects(self::any())
            ->method('getRelatedEntity')
            ->willReturn(self::ENTITY);

        $workflow->setDefinition($definition);

        $workflow->expects(self::any())
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
        $transition->expects(self::any())
            ->method('isStart')
            ->willReturn(false);
        $transition->expects(self::any())
            ->method('isHidden')
            ->willReturn($isHidden);

        $step = $this->createMock(Step::class);
        $step->expects(self::any())
            ->method('isAllowedTransition')
            ->willReturn($isAllowedTransition);
        $stepManager = $this->createMock(StepManager::class);
        $stepManager->expects(self::any())
            ->method('getStep')
            ->willReturn($step);

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::any())
            ->method('isTransitionAvailable')
            ->willReturn($isAvailable);
        $workflow->expects(self::any())
            ->method('getStepManager')
            ->willReturn($stepManager);

        if (true === $isExistWorkflowItem) {
            $workflowItem = $this->createMock(WorkflowItem::class);
            $workflowItem->expects(self::any())
                ->method('getCurrentStep')
                ->willReturn((new WorkflowStep())->setName('test_step'));

            $workflow->expects(self::once())
                ->method('getWorkflowItemByEntityId')
                ->willReturn($workflowItem);
        }

        $button = $this->createMock(TransitionButton::class);
        $button->expects(self::any())
            ->method('getWorkflow')
            ->willReturn($workflow);
        $button->expects(self::any())
            ->method('getTransition')
            ->willReturn($transition);

        return $button;
    }
}
