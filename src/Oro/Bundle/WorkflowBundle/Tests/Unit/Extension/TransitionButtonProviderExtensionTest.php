<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\ActionBundle\Button\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Extension\TransitionButtonProviderExtension;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Button\TransitionButton;
use Oro\Bundle\WorkflowBundle\Model\TransitionManager;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class TransitionButtonProviderExtensionTest extends \PHPUnit_Framework_TestCase
{
    const DATAGRID_NAME = 'datagrid1';
    const ENTITY = 'entity1';

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
            ->disableOriginalConstructor()->getMock();

        $this->routeProvider = $this->createMock(RouteProviderInterface::class);

        $this->extension = new TransitionButtonProviderExtension(
            $this->workflowRegistry,
            $this->routeProvider
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->workflowRegistry, $this->routeProvider, $this->extension);
    }

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
        /** @var Transition|\PHPUnit_Framework_MockObject_MockObject $transition */
        $transition = $this->createMock(Transition::class);

        $transitionManager = $this->createMock(TransitionManager::class);
        $transitionManager->expects($this->any())
            ->method('getTransitions')
            ->willReturn(new ArrayCollection([$transition]));

        $workflow = $this->getWorkflow($transitionManager);

        $this->workflowRegistry->expects($this->once())
            ->method('getActiveWorkflows')
            ->willReturn(new ArrayCollection([$workflow]));

        if ($expected) {
            $buttonContext = (new ButtonContext())
                ->setEntity($entityClass)
                ->setDatagridName($datagrid);
            $buttons = [new TransitionButton($transition, $workflow, $buttonContext)];
        } else {
            $buttons = [];
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
        $createTransitionButton = function ($isAvailable, $isExistWorkflowItem = true) {
            $transition = $this->createMock(Transition::class);
            $transition->expects($this->any())
                ->method('isStart')->willReturn(false);

            $workflow = $this->getMockBuilder(Workflow::class)
                ->disableOriginalConstructor()->getMock();
            $workflow->expects($this->any())->method('isTransitionAllowed')
                ->willReturn($isAvailable);

            if (true === $isExistWorkflowItem) {
                $workflow->expects($this->once())
                    ->method('getWorkflowItemByEntityId')
                    ->willReturn($this->createMock(WorkflowItem::class));
            }

            $button = $this->getMockBuilder(TransitionButton::class)
                ->disableOriginalConstructor()->getMock();
            $button->expects($this->any())->method('getWorkflow')->willReturn($workflow);
            $button->expects($this->any())->method('getTransition')->willReturn($transition);

            return $button;
        };

        return [
            [
                'expected' => false,
                'button' => $createTransitionButton(true, false),
            ],
            [
                'expected' => false,
                'button' => $createTransitionButton(false, false),
            ],
            [
                'expected' => false,
                'button' => $createTransitionButton(false),
            ],
            [
                'expected' => true,
                'button' => $createTransitionButton(true),
            ]
        ];
    }

    /**
     * @param TransitionManager|\PHPUnit_Framework_MockObject_MockObject $transitionManager
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getWorkflow(TransitionManager $transitionManager)
    {
        /** @var Workflow|\PHPUnit_Framework_MockObject_MockObject $workflow */
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
