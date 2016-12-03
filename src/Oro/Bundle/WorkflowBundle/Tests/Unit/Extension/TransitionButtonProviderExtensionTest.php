<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonInterface;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\RouteProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowItemRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Extension\TransitionButtonProviderExtension;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\TransitionButton;
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

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var TransitionButtonProviderExtension */
    protected $extension;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->workflowRegistry = $this->getMockBuilder(WorkflowRegistry::class)
            ->disableOriginalConstructor()->getMock();
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->routeProvider = $this->getMock(RouteProviderInterface::class);

        $this->extension = new TransitionButtonProviderExtension(
            $this->workflowRegistry,
            $this->routeProvider,
            $this->doctrineHelper
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
        $transition = $this->getMock(Transition::class);

        $createButtonInstance = function ($className, $isStart) use ($transition) {
            $cloneTransition = clone $transition;

            $mockButton = $this->getMockBuilder($className)
                ->disableOriginalConstructor()->getMock();
            $cloneTransition->expects($this->once())
                ->method('isStart')->willReturn($isStart);
            $mockButton->expects($this->once())
                ->method('getTransition')->willReturn($cloneTransition);

            return $mockButton;
        };

        return [
            'startTransitionButton' => [
                'button' => $createButtonInstance(TransitionButton::class, true),
                'expected' => false
            ],
            'unSupportedButton' => [
                'button' => $createButtonInstance(ButtonInterface::class, false),
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
        $transition = $this->getMock(Transition::class);

        $transitionManager = $this->getMock(TransitionManager::class);
        $transitionManager->expects($this->any())
            ->method('getTransitions')
            ->willReturn(new ArrayCollection([$transition]));

        $workflow = $this->getWorkflow($transitionManager);

        $this->workflowRegistry->expects($this->once())->method('getActiveWorkflows')->willReturn([$workflow]);

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
            [
                'expected' => false,
                'entityClass' => 'class',
            ],
            [
                'expected' => false,
                'entityClass' => 'test_entity',
                'datagrid' => self::DATAGRID_NAME
            ],
            [
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
            $this->getMock(ButtonInterface::class),
            $this->getMock(ButtonSearchContext::class)
        );
    }

    /**
     * @dataProvider isAvailableDataProvider
     *
     * @param bool $expected
     * @param ButtonInterface $button
     * @param WorkflowItem $workflowItem
     */
    public function testIsAvailable($expected, ButtonInterface $button, WorkflowItem $workflowItem = null)
    {
        $repo = $this->getMockBuilder(WorkflowItemRepository::class)
            ->disableOriginalConstructor()->getMock();
        $repo->expects($this->once())->method('findOneByEntityMetadata')->willReturn($workflowItem);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')->willReturn($repo);

        $this->assertEquals($expected, $this->extension->isAvailable($button, new ButtonSearchContext()));
    }

    /**
     * @return array
     */
    public function isAvailableDataProvider()
    {
        $createTransitionButton = function ($isAvailable) {
            $transition = $this->getMock(Transition::class);
            $transition->expects($this->any())
                ->method('isStart')->willReturn(false);

            $workflow = $this->getMockBuilder(Workflow::class)
                ->disableOriginalConstructor()->getMock();
            $workflow->expects($this->any())->method('isTransitionAllowed')
                ->willReturn($isAvailable);

            $button = $this->getMockBuilder(TransitionButton::class)
                ->disableOriginalConstructor()->getMock();
            $button->expects($this->any())->method('getWorkflow')->willReturn($workflow);
            $button->expects($this->any())->method('getTransition')->willReturn($transition);
            return $button;
        };

        return [
            [
                'expected' => false,
                'button' => $createTransitionButton(true),
            ],
            [
                'expected' => false,
                'button' => $createTransitionButton(false),
            ],
            [
                'expected' => false,
                'button' => $createTransitionButton(false),
                'workflowItem' => $this->getMock(WorkflowItem::class)
            ],
            [
                'expected' => true,
                'button' => $createTransitionButton(true),
                'workflowItem' => $this->getMock(WorkflowItem::class)
            ]
        ];
    }

    /**
     * @param TransitionManager|\PHPUnit_Framework_MockObject_MockObject $transitionManager
     *
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getWorkflow(TransitionManager $transitionManager)
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $definition = $this->getMock(WorkflowDefinition::class);
        $definition->expects($this->any())->method('getDatagrids')->willReturn([self::DATAGRID_NAME]);
        $definition->expects($this->any())->method('getRelatedEntity')->willReturn(self::ENTITY);

        $workflow->expects($this->any())->method('getDefinition')->willReturn($definition);
        $workflow->expects($this->any())->method('getTransitionManager')->willReturn($transitionManager);

        return $workflow;
    }
}
