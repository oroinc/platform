<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Extension;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Helper\ApplicationsHelperInterface;
use Oro\Bundle\ActionBundle\Model\ButtonContext;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
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

    /** @var ApplicationsHelperInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $applicationsHelper;

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

        $this->applicationsHelper = $this->getMock(ApplicationsHelperInterface::class);

        $this->extension = new TransitionButtonProviderExtension($this->workflowRegistry, $this->applicationsHelper);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->workflowRegistry, $this->applicationsHelper, $this->extension);
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

        $transitionManager = $this->getMock(TransitionManager::class);
        $transitionManager->expects($this->once())
            ->method('getStartTransitions')
            ->willReturn(new ArrayCollection([$transition]));

        $workflow = $this->getWorkflow($transitionManager);

        $this->workflowRegistry->expects($this->once())->method('getActiveWorkflows')->willReturn([$workflow]);

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
        $this->assertEquals(
            [],
            $this->extension->find((new ButtonSearchContext())->setGroup(uniqid()))
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
     *
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getWorkflow(TransitionManager $transitionManager)
    {
        $workflow = $this->getMockBuilder(Workflow::class)
            ->disableOriginalConstructor()
            ->getMock();

        $workflow->expects($this->once())
            ->method('getInitEntities')
            ->willReturn([self::ENTITY_CLASS => ['transition1', 'transition2']]);

        $definition = (new WorkflowDefinition())->setRelatedEntity('entity_related');

        $workflow->expects($this->any())->method('getDefinition')->willReturn($definition);
        $workflow->expects($this->once())->method('getTransitionManager')->willReturn($transitionManager);

        return $workflow;
    }
}
