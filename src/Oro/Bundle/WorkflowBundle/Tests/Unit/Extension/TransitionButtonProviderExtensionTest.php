<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Extension\TransitionButtonProviderExtension;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionButton;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class TransitionButtonProviderExtensionTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'entity1';

    /** @var WorkflowRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $workflowRegistry;

    /** @var RouteProviderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $routeProvider;

    /** @var TransitionButtonProviderExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->workflowRegistry = $this->getMockBuilder(WorkflowRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->routeProvider = $this->createMock(RouteProviderInterface::class);

        $this->extension = new TransitionButtonProviderExtension($this->workflowRegistry, $this->routeProvider);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->workflowRegistry, $this->routeProvider, $this->extension);
    }

    /**
     * @dataProvider findDataProvider
     *
     * @param string $entityClass
     * @param bool $isAvailable
     * @param bool $isUnavailableHidden
     * @param bool $expected
     */
    public function testFind($entityClass, $isAvailable, $isUnavailableHidden, $expected)
    {
        /** @var Transition|\PHPUnit_Framework_MockObject_MockObject $transition */
        $transition = $this->getMockBuilder(Transition::class)->setMethods(['isAvailable'])->getMock();
        $transition->setName('transition1')
            ->setInitEntities([$entityClass])
            ->setUnavailableHidden($isUnavailableHidden);
        $transition->expects($this->any())->method('isAvailable')->willReturn($isAvailable);

        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($this->once())
            ->method('getStartTransitions')
            ->willReturn(new ArrayCollection([$transition]));

        $workflow = $this->getWorkflow(
            $transitionManager,
            [
                'init_entities' => [
                    self::ENTITY_CLASS => ['transition1', 'transition2'],
                ],
            ]
        );

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflows')
            ->willReturn(new ArrayCollection([$workflow]));

        if ($expected) {
            $buttonContext = (new ButtonContext())->setEntity($entityClass)
                ->setUnavailableHidden($isUnavailableHidden)
                ->setEnabled($isAvailable || $isUnavailableHidden);
            $buttons = [new TransitionButton($transition, $workflow, $buttonContext)];
        } else {
            $buttons = [];
        }

        $this->assertEquals(
            $buttons,
            $this->extension->find((new ButtonSearchContext())->setEntity($entityClass))
        );
    }

    public function testFindWithGroupAtContext()
    {
        $this->workflowRegistry->expects($this->never())->method('getActiveWorkflows');
        $this->assertEquals(
            [],
            $this->extension->find((new ButtonSearchContext())->setGroup(uniqid()))
        );
    }

    public function testFindWithGridNameAtContext()
    {
        $this->workflowRegistry->expects($this->never())->method('getActiveWorkflows');
        $this->assertEquals(
            [],
            $this->extension->find((new ButtonSearchContext())->setGridName(uniqid()))
        );
    }

    public function testFindWithExclusiveRecordGroups()
    {
        $configuration = [
            'init_entities' => [
                'entity1' => [
                    'transition1',
                    'transition2',
                    'transition3',
                    'transition4',
                    'transition5',
                ],
            ],

        ];

        $workflow1 = $this->getWorkflow(
            $this->getTransitionManager([$this->getTransition('transition1'), $this->getTransition('transition2')]),
            $configuration,
            ['group1', 'group2']
        );

        $workflow2 = $this->getWorkflow(
            $this->getTransitionManager([$this->getTransition('transition3'), $this->getTransition('transition4')]),
            $configuration,
            ['group2', 'group3']
        );

        $workflow3 = $this->getWorkflow(
            $this->getTransitionManager([$this->getTransition('transition5'), $this->getTransition('transition6')]),
            $configuration,
            ['group3', 'group4']
        );

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflows')
            ->willReturn(new ArrayCollection([$workflow1, $workflow2, $workflow3]));

        $this->assertEquals(
            [
                new TransitionButton(
                    $this->getTransition('transition1'),
                    $workflow1,
                    $this->getButtonContext('entity1')
                ),
                new TransitionButton(
                    $this->getTransition('transition2'),
                    $workflow1,
                    $this->getButtonContext('entity1')
                ),
                new TransitionButton(
                    $this->getTransition('transition5'),
                    $workflow3,
                    $this->getButtonContext('entity1')
                ),
            ],
            $this->extension->find((new ButtonSearchContext())->setEntity('entity1'))
        );
    }

    /**
     * @return array
     */
    public function findDataProvider()
    {
        return [
            'available' => [
                'initEntities' => self::ENTITY_CLASS,
                'isAvailable' => true,
                'isUnavailableHidden' => true,
                'expected' => true,
            ],
            'not available' => [
                'initEntities' => self::ENTITY_CLASS,
                'isAvailable' => false,
                'isUnavailableHidden' => true,
                'expected' => false,
            ],
            'not matched but context' => [
                'initEntities' => 'other_entity',
                'isAvailable' => true,
                'isUnavailableHidden' => true,
                'expected' => false,
            ],
            'not isUnavailableHidden' => [
                'initEntities' => self::ENTITY_CLASS,
                'isAvailable' => false,
                'isUnavailableHidden' => false,
                'expected' => true,
            ],
        ];
    }

    /**
     * @param TransitionManager|\PHPUnit_Framework_MockObject_MockObject $transitionManager
     * @param array $configuration
     * @param array $exclusiveRecordGroups
     *
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getWorkflow(
        TransitionManager $transitionManager,
        array $configuration = [],
        array $exclusiveRecordGroups = []
    ) {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->setMethods(['getTransitionManager'])
            ->disableOriginalConstructor()
            ->getMock();

        $definition = (new WorkflowDefinition())
            ->setRelatedEntity('entity_related')
            ->setConfiguration($configuration)
            ->setExclusiveRecordGroups($exclusiveRecordGroups);

        $workflow->setDefinition($definition);

        $workflow->expects($this->any())->method('getTransitionManager')->willReturn($transitionManager);

        return $workflow;
    }

    /**
     * @param string $entityClass
     * @return ButtonContext
     */
    protected function getButtonContext($entityClass)
    {
        $context = new ButtonContext();
        $context->setEntity($entityClass)
            ->setEnabled(true)
            ->setUnavailableHidden(false);

        return $context;
    }

    /**
     * @param array $transitions
     * @return TransitionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getTransitionManager(array $transitions)
    {
        $manager = $this->createMock(TransitionManager::class);
        $manager->expects($this->any())
            ->method('getStartTransitions')
            ->willReturn(new ArrayCollection($transitions));

        return $manager;
    }

    /**
     * @param string $name
     * @return Transition
     */
    protected function getTransition($name)
    {
        $transition = new Transition();
        $transition->setName($name);

        return $transition;
    }
}
