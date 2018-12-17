<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\CurrentApplicationProviderInterface;
use Oro\Bundle\ActionBundle\Provider\OriginalUrlProvider;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Button\StartTransitionButton;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Extension\DatagridStartTransitionButtonProviderExtension;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;

class DatagridStartTransitionButtonProviderExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    protected $workflowRegistry;

    /** @var RouteProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $routeProvider;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var OriginalUrlProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $originalUrlProvider;

    /** @var CurrentApplicationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $applicationProvider;

    /** @var DatagridStartTransitionButtonProviderExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->routeProvider = $this->createMock(RouteProviderInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->originalUrlProvider = $this->createMock(OriginalUrlProvider::class);
        $this->applicationProvider = $this->createMock(CurrentApplicationProviderInterface::class);

        $this->extension = new DatagridStartTransitionButtonProviderExtension(
            $this->workflowRegistry,
            $this->routeProvider,
            $this->originalUrlProvider,
            $this->doctrineHelper
        );
        $this->extension->setApplicationProvider($this->applicationProvider);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->workflowRegistry,
            $this->routeProvider,
            $this->extension,
            $this->doctrineHelper,
            $this->originalUrlProvider
        );
    }

    /**
     * @dataProvider findDataProvider
     *
     * @param bool $expected
     * @param WorkflowDefinition $workflowDefinition
     * @param ButtonSearchContext $searchContext
     * @param string $application
     */
    public function testFind(
        $expected,
        WorkflowDefinition $workflowDefinition,
        ButtonSearchContext $searchContext,
        $application = CurrentApplicationProviderInterface::DEFAULT_APPLICATION
    ) {
        $transition = $this->createStartTransition();

        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($expected ? $this->once() : $this->never())
            ->method('getStartTransitions')
            ->willReturn(new ArrayCollection([$transition]));

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())->method('getDefinition')->willReturn($workflowDefinition);
        $workflow->expects($this->any())->method('getTransitionManager')->willReturn($transitionManager);

        $this->workflowRegistry->expects($this->any())
            ->method('getActiveWorkflows')
            ->willReturn(new ArrayCollection([$workflow]));

        $this->applicationProvider->expects($this->any())->method('getCurrentApplication')->willReturn($application);

        if ($expected) {
            $this->originalUrlProvider->expects($this->once())->method('getOriginalUrl')->willReturn('example.com');

            $buttonContext = (new ButtonContext())
                ->setEntity($searchContext->getEntityClass())
                ->setOriginalUrl('example.com')
                ->setDatagridName($searchContext->getDatagrid());
            $buttons = [new StartTransitionButton($transition, $workflow, $buttonContext)];
        } else {
            $this->originalUrlProvider->expects($this->never())->method('getOriginalUrl');

            $buttons = [];
        }

        $this->assertEquals(
            $buttons,
            $this->extension->find($searchContext)
        );
    }

    /**
     * @return \Generator
     */
    public function findDataProvider()
    {
        $wd1 = $this->createWorkflowDefinition('entity1');
        $wd2 = $this->createWorkflowDefinition('entity1', ['datagrid1']);

        yield 'empty node datagrids' => [
            'expected' => false,
            'workflowDefinition' => $wd1,
            'searchContext' => $this->createSearchContext('entity1', null, 'datagrid1')
        ];

        yield 'not matches datagrid' => [
            'expected' => false,
            'workflowDefinition' => $wd2,
            'searchContext' => $this->createSearchContext('entity1', null, 'datagrid2')
        ];

        yield 'not matches entity' => [
            'expected' => false,
            'workflowDefinition' => $wd2,
            'searchContext' => $this->createSearchContext('entity2', null, 'datagrid1')
        ];

        yield 'when find button' => [
            'expected' => true,
            'workflowDefinition' => $wd2,
            'searchContext' => $this->createSearchContext('entity1', null, 'datagrid1')
        ];

        yield 'when find button but incorrect application' => [
            'expected' => false,
            'workflowDefinition' => $wd2,
            'searchContext' => $this->createSearchContext('entity1', null, 'datagrid1'),
            'application' => 'test'
        ];
    }

    /**
     * @dataProvider isAvailableDataProvider
     *
     * @param Workflow|\PHPUnit\Framework\MockObject\MockObject $workflow
     * @param ButtonSearchContext $searchContext
     * @param bool $expected
     */
    public function testIsAvailable(Workflow $workflow, ButtonSearchContext $searchContext, $expected)
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflow->expects($this->once())->method('createWorkflowItem')->willReturn($workflowItem);
        $button = $this->createMock(StartTransitionButton::class);
        $button->expects($this->any())->method('getWorkflow')->willReturn($workflow);
        $button->expects($this->any())->method('getTransition')->willReturn($this->createStartTransition());

        $this->assertSame($expected, $this->extension->isAvailable($button, $searchContext));
    }

    /**
     * @return \Generator
     */
    public function isAvailableDataProvider()
    {
        yield 'is not available' => [
            'workflow' => $this->createWorkflow(false, false),
            'searchContext' => $this->createSearchContext('entity1', 10, 'grid1'),
            'expected' => false
        ];

        yield 'already started' => [
            'workflow' => $this->createWorkflow(false, true),
            'searchContext' => $this->createSearchContext('entity1', 10, 'grid1'),
            'expected' => false
        ];

        yield 'mussing entity id' => [
            'workflow' => $this->createWorkflow(true, false),
            'searchContext' => $this->createSearchContext('entity1', null, 'grid1'),
            'expected' => false
        ];

        yield 'is available' => [
            'workflow' => $this->createWorkflow(true, false),
            'searchContext' => $this->createSearchContext('entity1', 10, 'grid1'),
            'expected' => true
        ];
    }

    /**
     * @param bool $isStartAvailable
     * @param bool $isStarted
     * @return Workflow|\PHPUnit\Framework\MockObject\MockObject
     */
    public function createWorkflow($isStartAvailable, $isStarted)
    {
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())->method('isStartTransitionAvailable')->willReturn($isStartAvailable);
        $workflow->expects($this->any())->method('getWorkflowItemByEntityId')
            ->willReturn($isStarted ? new WorkflowItem() : null);

        return $workflow;
    }

    /**
     * @param string $entityClass
     * @param mixed $entityId
     * @param string $datagrid
     *
     * @return ButtonSearchContext
     */
    private function createSearchContext($entityClass, $entityId, $datagrid)
    {
        return (new ButtonSearchContext())->setEntity($entityClass, $entityId)->setDatagrid($datagrid);
    }

    /**
     * @param $relatedEntity
     * @param array $datagrids
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|WorkflowDefinition
     */
    private function createWorkflowDefinition($relatedEntity, array  $datagrids = [])
    {
        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects($this->any())->method('getDatagrids')->willReturn($datagrids);
        $workflowDefinition->expects($this->any())->method('getRelatedEntity')->willReturn($relatedEntity);
        $workflowDefinition->expects($this->any())->method('getExclusiveRecordGroups')->willReturn([]);

        return $workflowDefinition;
    }

    /**
     * @return Transition
     */
    private function createStartTransition()
    {
        $transition = new Transition($this->createMock(TransitionOptionsResolver::class));

        return $transition->setStart(true);
    }
}
