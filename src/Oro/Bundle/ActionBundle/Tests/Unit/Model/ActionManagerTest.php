<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionAssembler;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionDefinition;
use Oro\Bundle\ActionBundle\Model\ActionManager;
use Oro\Bundle\ActionBundle\Model\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\FormOptionsAssembler;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Action\Action\ActionFactory as FunctionFactory;
use Oro\Component\Action\Condition\Configurable as ConfigurableCondition;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ActionManagerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ContextHelper */
    protected $contextHelper;

    /** @var ActionConfigurationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FunctionFactory $functionFactory */
    protected $functionFactory;

    /** @var ConditionFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $conditionFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AttributeAssembler */
    protected $attributeAssembler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormOptionsAssembler */
    protected $formOptionsAssembler;

    /** @var ActionAssembler */
    protected $assembler;

    /** @var ApplicationsHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $applicationsHelper;

    /** @var ActionManager */
    protected $manager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($class) {
                return $class;
            });
        $this->doctrineHelper->expects($this->any())
            ->method('isManageableEntity')
            ->willReturn(true);

        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurationProvider = $this
            ->getMockBuilder('Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->functionFactory = $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->conditionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();

        $this->attributeAssembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\AttributeAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->formOptionsAssembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\FormOptionsAssembler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurationProvider->expects($this->any())
            ->method('getActionConfiguration')
            ->willReturn($this->getConfiguration());

        $this->assembler = new ActionAssembler(
            $this->functionFactory,
            $this->conditionFactory,
            $this->attributeAssembler,
            $this->formOptionsAssembler
        );

        $this->applicationsHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ApplicationsHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = new ActionManager(
            $this->doctrineHelper,
            $this->contextHelper,
            $this->configurationProvider,
            $this->assembler,
            $this->applicationsHelper
        );
    }

    /**
     * @param array $context
     * @param array $expectedData
     *
     * @dataProvider getActionsProvider
     */
    public function testHasActions(array $context, array $expectedData)
    {
        $this->assertApplicationsHelperCalled();
        $this->assertContextHelperCalled($context);
        $this->assertEquals($expectedData['hasActions'], $this->manager->hasActions($context));
    }

    /**
     * @param array $context
     * @param array $expectedData
     *
     * @dataProvider getActionsProvider
     */
    public function testGetActions(array $context, array $expectedData)
    {
        $this->assertApplicationsHelperCalled();
        $this->assertContextHelperCalled($context);

        if (isset($context['entityClass'])) {
            if (isset($context['entityId'])) {
                $this->doctrineHelper->expects($this->any())
                    ->method('getEntityReference')
                    ->willReturnCallback(function ($className, $id) {
                        $obj = new \stdClass();
                        $obj->id = $id;

                        return $obj;
                    });
            } else {
                $this->doctrineHelper->expects($this->any())
                    ->method('createEntityInstance')
                    ->willReturn(new \stdClass());
            }
        }

        $this->assertGetActions($expectedData['actions'], $context);
    }

    /**
     * @dataProvider getActionDataProvider
     *
     * @param string $actionName
     * @param $checkAvailable
     * @param mixed $expected
     */
    public function testGetAction($actionName, $checkAvailable, $expected)
    {
        $this->conditionFactory
            ->expects($this->any())
            ->method('create')
            ->withAnyParameters()
            ->willReturn($this->createCondition(false));

        if (!$expected) {
            $this->setExpectedException(
                '\Oro\Bundle\ActionBundle\Exception\ActionNotFoundException',
                sprintf('Action with name "%s" not found', $actionName)
            );
        }

        $this->assertApplicationsHelperCalled();

        $action = $this->manager->getAction($actionName, new ActionData(), $checkAvailable);

        if ($expected) {
            $this->assertEquals($expected, $action->getName());
        }
    }

    /**
     * @return array
     */
    public function getActionDataProvider()
    {
        return [
            'invalid action name' => [
                'actionName' => 'test',
                true,
                'expected' => null
            ],
            'valid action name' => [
                'actionName' => 'action2',
                true,
                'expected' => 'action2'
            ],
            'valid action name with wrong preconditions' => [
                'actionName' => 'action_wrong_preconditions',
                true,
                'expected' => null
            ],
            'valid action name with wrong preconditions without checking' => [
                'actionName' => 'action_wrong_preconditions',
                false,
                'expected' => 'action_wrong_preconditions'
            ],
        ];
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     *
     * @dataProvider getActionsAndMultipleCallsProvider
     */
    public function testGetActionsAndMultipleCalls(array $inputData, array $expectedData)
    {
        $this->assertApplicationsHelperCalled();

        $this->assertGetActions($expectedData['actions1'], $inputData['context1']);
        $this->assertGetActions($expectedData['actions2'], $inputData['context2']);
        $this->assertGetActions($expectedData['actions3'], $inputData['context3']);
    }

    /**
     * @dataProvider executeByContextDataProvider
     *
     * @param array $context
     * @param ActionData $actionData
     */
    public function testExecuteByContext(array $context, ActionData $actionData)
    {
        $this->assertApplicationsHelperCalled();
        if ($actionData->getEntity()) {
            $this->assertEntityManagerCalled('stdClass');
        }

        $this->contextHelper->expects($this->once())
            ->method('getActionData')
            ->willReturn($actionData);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback(function ($className, $id) {
                $obj = new $className();
                $obj->id = $id;

                return $obj;
            });

        $action = $this->createActionMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionAssembler $assembler */
        $assembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionAssembler')
            ->disableOriginalConstructor()
            ->getMock();
        $assembler->expects($this->once())
            ->method('assemble')
            ->willReturn(['test_action' => $action]);

        $this->manager = new ActionManager(
            $this->doctrineHelper,
            $this->contextHelper,
            $this->configurationProvider,
            $assembler,
            $this->applicationsHelper
        );

        $errors = new ArrayCollection();

        $this->manager->executeByContext('test_action', $context, $errors);

        $this->assertEmpty($errors->toArray());
    }

    /**
     * @dataProvider executeByActionDataDataProvider
     *
     * @param ActionData $actionData
     */
    public function testExecute(ActionData $actionData)
    {
        if ($actionData->getEntity()) {
            $this->assertEntityManagerCalled(get_class($actionData->getEntity()));
        }

        $this->assertApplicationsHelperCalled();
        $action = $this->createActionMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionAssembler $assembler */
        $assembler = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionAssembler')
            ->disableOriginalConstructor()
            ->getMock();
        $assembler->expects($this->once())
            ->method('assemble')
            ->willReturn(['test_action' => $action]);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback(function ($className, $id) {
                $obj = new $className();
                $obj->id = $id;

                return $obj;
            });

        $this->manager = new ActionManager(
            $this->doctrineHelper,
            $this->contextHelper,
            $this->configurationProvider,
            $assembler,
            $this->applicationsHelper
        );

        $errors = new ArrayCollection();

        $this->manager->execute('test_action', $actionData, $errors);

        $this->assertEmpty($errors->toArray());
    }

    /**
     * @param string $className
     * @param bool $throwException
     */
    protected function assertEntityManagerCalled($className, $throwException = false)
    {
        $entityManager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $entityManager->expects($this->once())
            ->method('beginTransaction');

        if ($throwException) {
            $entityManager->expects($this->once())
                ->method('flush')
                ->willThrowException(new \Exception());
            $entityManager->expects($this->once())
                ->method('rollback');
        } else {
            $entityManager->expects($this->once())
                ->method('flush');
            $entityManager->expects($this->once())
                ->method('commit');
        }

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with($this->isInstanceOf($className))
            ->willReturn($entityManager);
    }

    /**
     * @return Action|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createActionMock()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ActionDefinition $actionDefinition */
        $actionDefinition = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionDefinition')
            ->disableOriginalConstructor()
            ->getMock();
        $actionDefinition->expects($this->once())
            ->method('getRoutes')
            ->willReturn(['route1']);
        $actionDefinition->expects($this->once())
            ->method('getEntities')
            ->willReturn(['stdClass']);
        $actionDefinition->expects($this->once())
            ->method('getDatagrids')
            ->willReturn(['datagrid1']);

        /** @var \PHPUnit_Framework_MockObject_MockObject|Action $action */
        $action = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Action')
            ->disableOriginalConstructor()
            ->getMock();
        $action->expects($this->any())
            ->method('getDefinition')
            ->willReturn($actionDefinition);
        $action->expects($this->any())
            ->method('getName')
            ->willReturn('test_action');
        $action->expects($this->once())
            ->method('isEnabled')
            ->willReturn(true);
        $action->expects($this->once())
            ->method('isAvailable')
            ->willReturn(true);
        $action->expects($this->once())
            ->method('execute')
            ->willReturn(true);

        return $action;
    }

    /**
     * @return array
     */
    public function executeByContextDataProvider()
    {
        return [
            'route1' => [
                'context' => [
                    'route' => 'route1'
                ],
                'actionData' => new ActionData(),
            ],
            'route1 without entity id' => [
                'context' => [
                    'route' => 'route1',
                    'entityClass' => 'stdClass'
                ],
                'actionData' => new ActionData(),
            ],
            'entity' => [
                'context' => [
                    'entityClass' => 'stdClass',
                    'entityId' => 1
                ],
                'actionData' => new ActionData(),
            ],
            'route1 and entity' => [
                'context' => [
                    'route' => 'route1',
                    'entityClass' => 'stdClass',
                    'entityId' => 1
                ],
                'actionData' => new ActionData(),
            ],
            'route1 and entity with action data and entity' => [
                'context' => [
                    'route' => 'route1',
                    'entityClass' => 'stdClass',
                    'entityId' => 1
                ],
                'actionData' => new ActionData(['data' => new \stdClass]),
            ],
        ];
    }

    /**
     * @return array
     */
    public function executeByActionDataDataProvider()
    {
        return [
            'empty context' => [
                'actionData' => new ActionData(),
                'exceptionMessage' => null,
            ],
            'entity' => [
                'actionData' => new ActionData(['data' => new \stdClass]),
                'exceptionMessage' => null,
            ],
            'exception' => [
                'actionData' => new ActionData(['data' => new \stdClass]),
                'exceptionMessage' => 'Action with name "test_action" not found',
            ],
        ];
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\ActionNotFoundException
     * @expectedExceptionMessage Action with name "test_action" not found
     */
    public function testExecuteByContextException()
    {
        $this->assertContextHelperCalled([], 0, 1);

        $this->manager->executeByContext('test_action');
    }

    /**
     * @dataProvider getFrontendTemplateDataProvider
     *
     * @param string $actionName
     * @param string $expected
     */
    public function testGetFrontendTemplate($actionName, $expected)
    {
        $this->assertApplicationsHelperCalled();
        $this->assertContextHelperCalled(
            [
                'route' => 'route1',
                'entityClass' => 'stdClass',
                'entityId' => 1,
                'datagrid' => 'datagrid_name',
            ],
            0,
            1
        );

        $this->assertEquals($expected, $this->manager->getFrontendTemplate($actionName));
    }

    /**
     * @param array $expectedActions
     * @param array $inputContext
     */
    protected function assertGetActions(array $expectedActions, array $inputContext)
    {
        $this->contextHelper->expects($this->any())
            ->method('getContext')
            ->willReturnCallback(function ($context) {
                return array_merge(
                    ['route' => null, 'entityId' => null, 'entityClass' => null, 'datagrid' => null],
                    $context
                );
            });

        $this->contextHelper->expects($this->any())
            ->method('getActionData')
            ->willReturn(new ActionData());

        $this->assertEquals($expectedActions, array_keys($this->manager->getActions($inputContext)));
    }

    /**
     * @param array $context
     * @param int $getContextCalls
     * @param int $getActionDataCalls
     */
    protected function assertContextHelperCalled(array $context = [], $getContextCalls = 1, $getActionDataCalls = 1)
    {
        $this->contextHelper->expects($this->exactly($getContextCalls))
            ->method('getContext')
            ->willReturn(
                array_merge(
                    [
                        'route' => null,
                        'entityId' => null,
                        'entityClass' => null,
                        'datagrid' => null,
                    ],
                    $context
                )
            );

        $this->contextHelper->expects($this->exactly($getActionDataCalls))
            ->method('getActionData')
            ->willReturn(new ActionData());
    }

    /**
     * @return array
     */
    public function getActionsProvider()
    {
        return [
            'empty context' => [
                'context' => [],
                'expected' => [
                    'actions' => [],
                    'hasActions' => false,
                ],
            ],
            'incorrect context parameter' => [
                'context' => [
                    'entityId' => 1,
                ],
                'expected' => [
                    'actions' => [],
                    'hasActions' => false,
                ],
            ],
            'route1' => [
                'context' => [
                    'route' => 'route1',
                ],
                'expected' => [
                    'actions' => [
                        'action4',
                        'action2',
                    ],
                    'hasActions' => true,
                ],
            ],
            'entity1 without id' => [
                'context' => [
                    'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                ],
                'expected' => [
                    'actions' => [],
                    'hasActions' => false,
                ],
            ],
            'entity1' => [
                'context' => [
                    'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'entityId' => 1,
                ],
                'expected' => [
                    'actions' => [
                        'action4',
                        'action3',
                        'action6'
                    ],
                    'hasActions' => true,
                ],
            ],
            'route1 & entity1' => [
                'context' => [
                    'route' => 'route1',
                    'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'entityId' => 1,
                ],
                'expected' => [
                    'actions' => [
                        'action4',
                        'action3',
                        'action2',
                        'action6'
                    ],
                    'hasActions' => true,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getActionsAndMultipleCallsProvider()
    {
        return [
            [
                'input' => [
                    'context1' => [],
                    'context2' => [
                        'route' => 'route1',
                    ],
                    'context3' => [
                        'route' => 'route2',
                        'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                        'entityId' => '2',
                    ],
                ],
                'expected' => [
                    'actions1' => [],
                    'actions2' => [
                        'action4',
                        'action2',
                    ],
                    'actions3' => [
                        'action4',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getFrontendTemplateDataProvider()
    {
        return [
            [
                'actionName' => 'action2',
                'expected' => ActionManager::DEFAULT_FORM_TEMPLATE
            ],
            [
                'actionName' => 'action1',
                'expected' => ActionManager::DEFAULT_PAGE_TEMPLATE
            ],
            [
                'actionName' => 'action4',
                'expected' => 'test.html.twig'
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getConfiguration()
    {
        return [
            'action1' => [
                'label' => 'Label1',
                'routes' => [],
                'entities' => [],
                'order' => 50,
                'frontend_options' => ['show_dialog' => false]
            ],
            'action2' => [
                'label' => 'Label2',
                'routes' => [
                    'route1',
                ],
                'entities' => [],
                'order' => 40,
                'frontend_options' => ['show_dialog' => true]
            ],
            'action3' => [
                'label' => 'Label3',
                'routes' => [],
                'entities' => [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                ],
                'order' => 30,
                'frontend_options' => ['show_dialog' => true]
            ],
            'action4' => [
                'label' => 'Label4',
                'routes' => [
                    'route1',
                    'route2',
                ],
                'entities' => [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                ],
                'frontend_options' => [
                    'template' => 'test.html.twig',
                    'show_dialog' => true
                ],
                'order' => 20
            ],
            'action5' => [
                'label' => 'Label5',
                'routes' => [
                    'route2',
                    'route3',
                ],
                'entities' => [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity3',
                ],
                'order' => 10,
                'enabled' => false,
                'frontend_options' => ['show_dialog' => true]
            ],
            'action6' => [
                'label' => 'Label6',
                'applications' => ['backend'],
                'routes' => [],
                'entities' => ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'],
                'order' => 50,
                'enabled' => true,
                'frontend_options' => ['show_dialog' => true]
            ],
            'action7' => [
                'label' => 'Label7',
                'applications' => ['frontend'],
                'routes' => [
                    'route1',
                    'route2',
                    'route3',
                ],
                'entities' => [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity3',
                ],
                'order' => 50,
                'enabled' => true,
                'frontend_options' => ['show_dialog' => true]
            ],
            'action_wrong_preconditions' => [
                'label' => 'Label2',
                'entities' => [],
                'order' => 40,
                'frontend_options' => ['show_dialog' => true],
                'preconditions' => [$this->createCondition(false)],
            ],
        ];
    }

    protected function assertApplicationsHelperCalled()
    {
        $this->applicationsHelper->expects($this->any())
            ->method('isApplicationsValid')
            ->willReturnCallback(
                function (Action $action) {
                    if (count($action->getDefinition()->getApplications()) === 0) {
                        return true;
                    }

                    return in_array('backend', $action->getDefinition()->getApplications(), true);
                }
            );
    }

    /**
     * @param bool $returnValue
     * @return ConfigurableCondition|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createCondition($returnValue)
    {
        /* @var $condition ConfigurableCondition|\PHPUnit_Framework_MockObject_MockObject */
        $condition = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Condition\Configurable')
            ->disableOriginalConstructor()
            ->getMock();

        $condition->expects($this->any())
            ->method('evaluate')
            ->withAnyParameters()
            ->willReturn($returnValue);

        return $condition;
    }
}
