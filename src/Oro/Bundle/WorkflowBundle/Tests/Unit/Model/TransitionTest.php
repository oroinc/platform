<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Resolver\TransitionOptionsResolver;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\ConfigExpression\ExpressionInterface;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TransitionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TransitionOptionsResolver */
    protected $optionsResolver;

    /** @var Transition */
    protected $transition;

    protected function setUp()
    {
        $this->optionsResolver = $this->createMock(TransitionOptionsResolver::class);
        $this->transition = new Transition($this->optionsResolver);
    }

    public function testAccessors()
    {
        $this->assertPropertyAccessors(
            $this->transition,
            [
                ['name', 'test'],
                ['buttonLabel', 'test_button_label'],
                ['buttonTitle', 'test_button_title'],
                ['hidden', true, false],
                ['start', true, false],
                ['unavailableHidden', true],
                ['stepTo', $this->getStepMock('testStep')],
                ['frontendOptions', ['key' => 'value'], []],
                ['formType', 'custom_workflow_transition'],
                ['displayType', 'page'],
                ['destinationPage', 'destination'],
                ['formOptions', ['one', 'two'], []],
                ['pageTemplate', 'Workflow:Test:page_template.html.twig'],
                ['dialogTemplate', 'Workflow:Test:dialog_template.html.twig'],
                ['scheduleCron', '1 * * * *'],
                ['scheduleFilter', "e.field < DATE_ADD(NOW(), 1, 'day')"],
                ['scheduleCheckConditions', true],
                ['preAction', $this->createMock(ActionInterface::class)],
                ['preCondition', $this->createMock(ExpressionInterface::class)],
                ['condition', $this->createMock(ExpressionInterface::class)],
                ['action', $this->createMock(ActionInterface::class)],
                ['initEntities', ['TEST_ENTITY_1', 'TEST_ENTITY_2', 'TEST_ENTITY_3']],
                ['initRoutes', ['TEST_ROUTE_1', 'TEST_ROUTE_2', 'TEST_ROUTE_3']],
                ['initContextAttribute', 'testInitContextAttribute'],
                ['message', 'test message'],
            ]
        );
    }

    public function testToString()
    {
        $this->transition->setName('test_transition');

        $this->assertEquals('test_transition', (string)$this->transition);
    }

    /**
     * @dataProvider isAllowedDataProvider
     *
     * @param bool $isAllowed
     * @param bool $expected
     */
    public function testIsAllowed($isAllowed, $expected)
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();

        if (null !== $isAllowed) {
            $condition = $this->createMock('Oro\Component\ConfigExpression\ExpressionInterface');
            $condition->expects($this->once())
                ->method('evaluate')
                ->with($workflowItem)
                ->will($this->returnValue($isAllowed));
            $this->transition->setCondition($condition);
        }

        $this->assertEquals($expected, $this->transition->isAllowed($workflowItem));
    }

    /**
     * @return array
     */
    public function isAllowedDataProvider()
    {
        return [
            'allowed' => [
                'isAllowed' => true,
                'expected' => true
            ],
            'not allowed' => [
                'isAllowed' => false,
                'expected' => false,
            ],
            'no condition' => [
                'isAllowed' => null,
                'expected' => true,
            ],
        ];
    }

    public function testIsPreConditionAllowedWithPreActions()
    {
        $workflowItem = $this->getMockBuilder(WorkflowItem::class)->disableOriginalConstructor()->getMock();

        $action = $this->createMock(ActionInterface::class);
        $action->expects($this->once())->method('execute')->with($workflowItem);
        $this->transition->setPreAction($action);

        $condition = $this->createMock(ExpressionInterface::class);
        $condition->expects($this->once())->method('evaluate')->with($workflowItem)->willReturn(true);
        $this->transition->setCondition($condition);

        $this->assertTrue($this->transition->isAllowed($workflowItem));
    }

    /**
     * @dataProvider isAllowedDataProvider
     *
     * @param bool $isAllowed
     * @param bool $expected
     */
    public function testIsAvailableWithForm($isAllowed, $expected)
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->transition->setFormOptions(['key' => 'value']);

        if (null !== $isAllowed) {
            $condition = $this->createMock('Oro\Component\ConfigExpression\ExpressionInterface');
            $condition->expects($this->once())
                ->method('evaluate')
                ->with($workflowItem)
                ->will($this->returnValue($isAllowed));
            $this->transition->setPreCondition($condition);
        }
        $this->optionsResolver->expects($this->once())
            ->method('resolveTransitionOptions')
            ->with($this->transition, $workflowItem);

        $this->assertEquals($expected, $this->transition->isAvailable($workflowItem));
    }

    /**
     * @dataProvider isAvailableDataProvider
     *
     * @param bool $isAllowed
     * @param bool $isAvailable
     * @param bool $expected
     */
    public function testIsAvailableWithoutForm($isAllowed, $isAvailable, $expected)
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();

        if (null !== $isAvailable) {
            $preCondition = $this->createMock('Oro\Component\ConfigExpression\ExpressionInterface');
            $preCondition->expects($this->any())
                ->method('evaluate')
                ->with($workflowItem)
                ->will($this->returnValue($isAvailable));
            $this->transition->setPreCondition($preCondition);
        }
        if (null !== $isAllowed) {
            $condition = $this->createMock('Oro\Component\ConfigExpression\ExpressionInterface');
            $condition->expects($this->any())
                ->method('evaluate')
                ->with($workflowItem)
                ->will($this->returnValue($isAllowed));
            $this->transition->setCondition($condition);
        }

        $this->assertEquals($expected, $this->transition->isAvailable($workflowItem));
    }

    /**
     * @return array
     */
    public function isAvailableDataProvider()
    {
        return [
            'allowed' => [
                'isAllowed' => true,
                'isAvailable' => true,
                'expected' => true
            ],
            'not allowed #1' => [
                'isAllowed' => false,
                'isAvailable' => true,
                'expected' => false,
            ],
            'not allowed #2' => [
                'isAllowed' => true,
                'isAvailable' => false,
                'expected' => false,
            ],
            'not allowed #3' => [
                'isAllowed' => false,
                'isAvailable' => false,
                'expected' => false,
            ],
            'no conditions' => [
                'isAllowed' => null,
                'isAvailable' => null,
                'expected' => true,
            ],
        ];
    }

    /**
     * @dataProvider transitDisallowedDataProvider
     *
     * @param bool $preConditionAllowed
     * @param bool $conditionAllowed
     *
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\ForbiddenTransitionException
     * @expectedExceptionMessage Transition "test" is not allowed.
     */
    public function testTransitNotAllowed($preConditionAllowed, $conditionAllowed)
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->never())
            ->method('setCurrentStep');

        $preCondition = $this->createMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $preCondition->expects($this->any())
            ->method('evaluate')
            ->with($workflowItem)
            ->will($this->returnValue($preConditionAllowed));

        $condition = $this->createMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $condition->expects($this->any())
            ->method('evaluate')
            ->with($workflowItem)
            ->will($this->returnValue($conditionAllowed));

        $action = $this->createMock('Oro\Component\Action\Action\ActionInterface');
        $action->expects($this->never())
            ->method('execute');

        $this->transition->setName('test')
            ->setPreCondition($preCondition)
            ->setCondition($condition)
            ->setAction($action)
            ->transit($workflowItem);
    }

    /**
     * @return array
     */
    public function transitDisallowedDataProvider()
    {
        return [
            [false, false],
            [false, true],
            [true, false]
        ];
    }

    /**
     * @dataProvider transitDataProvider
     *
     * @param boolean $isFinal
     * @param boolean $hasAllowedTransition
     */
    public function testTransit($isFinal, $hasAllowedTransition)
    {
        $currentStepEntity = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowStep')
            ->disableOriginalConstructor()
            ->getMock();

        $step = $this->getStepMock('currentStep', $isFinal, $hasAllowedTransition, $currentStepEntity);

        $workflowDefinition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowDefinition->expects($this->once())
            ->method('getStepByName')
            ->with($step->getName())
            ->will($this->returnValue($currentStepEntity));

        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $workflowItem->expects($this->once())
            ->method('getDefinition')
            ->will($this->returnValue($workflowDefinition));
        $workflowItem->expects($this->once())
            ->method('setCurrentStep')
            ->with($currentStepEntity);

        $preCondition = $this->createMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $preCondition->expects($this->once())
            ->method('evaluate')
            ->with($workflowItem)
            ->will($this->returnValue(true));

        $condition = $this->createMock('Oro\Component\ConfigExpression\ExpressionInterface');
        $condition->expects($this->once())
            ->method('evaluate')
            ->with($workflowItem)
            ->will($this->returnValue(true));

        $action = $this->createMock('Oro\Component\Action\Action\ActionInterface');
        $action->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $this->transition
            ->setPreCondition($preCondition)
            ->setCondition($condition)
            ->setAction($action)
            ->setStepTo($step)
            ->transit($workflowItem);
    }

    /**
     * @return array
     */
    public function transitDataProvider()
    {
        return [
            [true, true],
            [true, false],
            [false, false]
        ];
    }

    /**
     * @param string $name
     * @param bool $isFinal
     * @param bool $hasAllowedTransitions
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|Step
     */
    protected function getStepMock($name, $isFinal = false, $hasAllowedTransitions = true)
    {
        $step = $this->getMockBuilder(Step::class)->disableOriginalConstructor()->getMock();
        $step->expects($this->any())->method('getName')->willReturn($name);
        $step->expects($this->any())->method('isFinal')->willReturn($isFinal);
        $step->expects($this->any())->method('hasAllowedTransitions')->willReturn($hasAllowedTransitions);

        return $step;
    }

    public function testHasForm()
    {
        $this->assertFalse($this->transition->hasForm()); // by default transition has form

        $this->transition->setFormOptions(['key' => 'value']);
        $this->assertFalse($this->transition->hasForm());

        $this->transition->setFormOptions(['attribute_fields' => []]);
        $this->assertFalse($this->transition->hasForm());

        $this->transition->setFormOptions(['attribute_fields' => ['key' => 'value']]);
        $this->assertTrue($this->transition->hasForm());
    }

    public function testHasFormWithFormConfiguration()
    {
        $this->assertFalse($this->transition->hasForm()); // by default transition has form

        $this->transition->setFormOptions(['key' => 'value']);
        $this->assertFalse($this->transition->hasForm());

        $this->transition->setFormOptions(['configuration' => []]);
        $this->assertFalse($this->transition->hasForm());

        $this->transition->setFormOptions(['configuration' => ['key' => 'value']]);
        $this->assertTrue($this->transition->hasForm());
    }

    public function testHasFormForPage()
    {
        $this->assertFalse($this->transition->hasForm()); // by default transition has form

        $this->transition->setDisplayType('page');
        $this->assertTrue($this->transition->hasForm());
    }

    /**
     * @dataProvider initContextProvider
     *
     * @param array $entities
     * @param array $routes
     * @param array $datagrids
     * @param bool $result
     */
    public function testIsNotEmptyInitContext(array $entities, array $routes, array $datagrids, $result)
    {
        $this->transition->setInitEntities($entities)
            ->setInitRoutes($routes)
            ->setInitDatagrids($datagrids);
        $this->assertSame($result, $this->transition->isEmptyInitOptions());
    }

    /**
     * @return array
     */
    public function initContextProvider()
    {
        return [
            'empty' => [
                'entities' => [],
                'routes' => [],
                'datagrids' => [],
                'result' => true
            ],
            'only entity' => [
                'entities' => ['entity'],
                'routes' => [],
                'datagrids' => [],
                'result' => false
            ],
            'only route' => [
                'entities' => [],
                'routes' => ['route'],
                'datagrids' => [],
                'result' => false
            ],
            'only datagrid' => [
                'entities' => [],
                'routes' => [],
                'datagrids' => ['datagrid'],
                'result' => false
            ],
            'full' => [
                'entities' => ['entity'],
                'routes' => ['route'],
                'datagrids' => ['datagrid'],
                'result' => false
            ],
            'full with arrays' => [
                'entities' => ['entity1', 'entity2'],
                'routes' => ['route1', 'route2'],
                'datagrids' => ['datagrid1', 'datagrid2'],
                'result' => false
            ]
        ];
    }

    public function testFormOptionsConfiguration()
    {
        $this->assertEquals([], $this->transition->getFormOptions());
        $this->assertFalse($this->transition->hasFormConfiguration());

        $formConfiguration = [
            'handler' => 'handler',
            'template' => 'template',
            'data_provider' => 'data_provider',
            'data_attribute' => 'data_attribute',
        ];
        $formOptions = [WorkflowConfiguration::NODE_FORM_OPTIONS_CONFIGURATION => $formConfiguration];

        $this->transition->setFormOptions($formOptions);

        $this->assertTrue($this->transition->hasFormConfiguration());
        $this->assertEquals($formConfiguration['handler'], $this->transition->getFormHandler());
        $this->assertEquals($formConfiguration['template'], $this->transition->getFormTemplate());
        $this->assertEquals($formConfiguration['data_provider'], $this->transition->getFormDataProvider());
        $this->assertEquals($formConfiguration['data_attribute'], $this->transition->getFormDataAttribute());
    }
}
