<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider;
use Oro\Bundle\ActionBundle\Helper\ApplicationsHelper;
use Oro\Bundle\ActionBundle\Model\Action;
use Oro\Bundle\ActionBundle\Model\ActionAssembler;
use Oro\Bundle\ActionBundle\Model\ActionRegistry;
use Oro\Bundle\ActionBundle\Model\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\FormOptionsAssembler;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Component\ConfigExpression\Action\ActionFactory as FunctionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class ActionRegistryTest extends \PHPUnit_Framework_TestCase
{
    /** @var ActionConfigurationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurationProvider;

    /** @var ActionAssembler */
    protected $assembler;

    /** @var ApplicationsHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $applicationsHelper;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FunctionFactory */
    protected $functionFactory;

    /** @var ConditionFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $conditionFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AttributeAssembler */
    protected $attributeAssembler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormOptionsAssembler */
    protected $formOptionsAssembler;

    /** @var ActionRegistry */
    protected $registry;

    private $contextHelper;

    protected function setUp()
    {
        $this->contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configurationProvider = $this
            ->getMockBuilder('Oro\Bundle\ActionBundle\Configuration\ActionConfigurationProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->functionFactory = $this->getMockBuilder('Oro\Component\ConfigExpression\Action\ActionFactory')
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

        $this->applicationsHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ApplicationsHelper')
            ->disableOriginalConstructor()
            ->getMock();
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

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($class) {
                return $class;
            });

        $this->assembler = new ActionAssembler(
            $this->functionFactory,
            $this->conditionFactory,
            $this->attributeAssembler,
            $this->formOptionsAssembler,
            $this->doctrineHelper
        );

        $this->registry = new ActionRegistry(
            $this->configurationProvider,
            $this->assembler,
            $this->applicationsHelper
        );
    }

    /**
     * @dataProvider findDataProvider
     *
     * @param string $entityClass
     * @param string $route
     * @param string $datagrid
     * @param string $group
     * @param array $expected
     */
    public function testFind($entityClass, $route, $datagrid, $group, array $expected)
    {
        $this->configurationProvider->expects($this->once())
            ->method('getActionConfiguration')
            ->willReturn($this->getConfiguration());

        $this->assertEquals($expected, array_keys($this->registry->find($entityClass, $route, $datagrid, $group)));

        // get actions from local cache
        $this->assertEquals($expected, array_keys($this->registry->find($entityClass, $route, $datagrid, $group)));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function findDataProvider()
    {
        return [
            'empty parameters' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'expected' => []
            ],
            'incorrect parameters' => [
                'entityClass' => 'unknown',
                'route' => 'unknown',
                'datagrid' => 'unknown',
                'group' => 'unknown',
                'expected' => []
            ],
            'entity1' => [
                'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'expected' => ['action6', 'action10', 'action12', 'action13']
            ],
            'route1' => [
                'entityClass' => null,
                'route' => 'route1',
                'datagrid' => null,
                'group' => null,
                'expected' => ['action4', 'action10']
            ],
            'datagrid1' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => 'datagrid1',
                'group' => null,
                'expected' => ['action8']
            ],
            'entity1 group1' => [
                'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                'route' => null,
                'datagrid' => null,
                'group' => 'group1',
                'expected' => ['action7', 'action11']
            ],
            'route1 group1' => [
                'entityClass' => null,
                'route' => 'route1',
                'datagrid' => null,
                'group' => 'group1',
                'expected' => ['action5', 'action11']
            ],
            'datagrid1 group1' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' => 'datagrid1',
                'group' => 'group1',
                'expected' => ['action9']
            ],
            'route1 & entity1' => [
                'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                'route' => 'route1',
                'datagrid' => null,
                'group' => null,
                'expected' => ['action4', 'action6', 'action10', 'action12', 'action13']
            ],
            'route1 & datagrid1' => [
                'entityClass' => null,
                'route' => 'route1',
                'datagrid' => 'datagrid1',
                'group' => null,
                'expected' => ['action4', 'action8', 'action10']
            ],
            'route1 & entity1 & datagrid1' => [
                'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                'route' => 'route1',
                'datagrid' => 'datagrid1',
                'group' => null,
                'expected' => ['action4', 'action6', 'action8', 'action10', 'action12', 'action13']
            ],
            'route1 group1 & entity1 group1 & datagrid1 group1' => [
                'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                'route' => 'route1',
                'datagrid' => 'datagrid1',
                'group' => 'group1',
                'expected' => ['action5', 'action7', 'action9', 'action11']
            ],
            'entity2' => [
                'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                'route' => null,
                'datagrid' => null,
                'group' => null,
                'expected' => ['action10', 'action13', 'action14']
            ],
            'entity3 substitution of action15 by action16' => [
                'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity3',
                'route' => null,
                'datagrid' =>  null,
                'group' => null,
                'expected' => ['action13', 'action14', 'action16']
            ],
            'action17 matched by group but no substitution and no appearance' => [
                'entityClass' => null,
                'route' => null,
                'datagrid' =>  null,
                'group' => 'group4',
                'expected' => []
            ],
            'substitute conditional only for specific entity and common group' => [
                'entityClass' => 'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity3',
                'route' => null,
                'datagrid' =>  null,
                'group' => 'limited',
                'expected' => ['action19']
            ]
        ];
    }

    /**
     * @dataProvider findByNameDataProvider
     *
     * @param string $actionName
     * @param string|null $expected
     */
    public function testFindByName($actionName, $expected)
    {
        $this->configurationProvider->expects($this->once())
            ->method('getActionConfiguration')
            ->willReturn(
                [
                    'action1' => [
                        'label' => 'Label1'
                    ]
                ]
            );

        $action = $this->registry->findByName($actionName);

        $this->assertEquals($expected, $action ? $action->getName() : $action);
    }

    /**
     * @return array
     */
    public function findByNameDataProvider()
    {
        return [
            'invalid action name' => [
                'actionName' => 'test',
                'expected' => null
            ],
            'valid action name' => [
                'actionName' => 'action1',
                'expected' => 'action1'
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    protected function getConfiguration()
    {
        return [
            'action1' => [
                'label' => 'Label1'
            ],
            'action2' => [
                'label' => 'Label2',
                'enabled' => false,
                'entities' => [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity3',
                ],
                'routes' => ['route1', 'route2', 'route3'],
                'datagrids' => ['datagrid1', 'datagrid2', 'datagrid3']
            ],
            'action3' => [
                'label' => 'Label3',
                'applications' => ['frontend'],
                'groups' => ['group1'],
                'entities' => [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity3',
                ],
                'routes' => ['route1', 'route2', 'route3'],
                'datagrids' => ['datagrid1', 'datagrid2', 'datagrid3']
            ],
            'action4' => [
                'label' => 'Label4',
                'routes' => ['route1']
            ],
            'action5' => [
                'label' => 'Label5',
                'groups' => ['group1'],
                'routes' => ['route1']
            ],
            'action6' => [
                'label' => 'Label6',
                'entities' => ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1']
            ],
            'action7' => [
                'label' => 'Label7',
                'groups' => ['group1'],
                'entities' => ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1']
            ],
            'action8' => [
                'label' => 'Label8',
                'datagrids' => ['datagrid1'],
            ],
            'action9' => [
                'label' => 'Label9',
                'groups' => ['group1'],
                'datagrids' => ['datagrid1'],
            ],
            'action10' => [
                'label' => 'Label10',
                'entities' => [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                ],
                'routes' => ['route1', 'route2']
            ],
            'action11' => [
                'label' => 'Label11',
                'groups' => ['group1'],
                'entities' => [
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1',
                    'Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2',
                ],
                'routes' => ['route1', 'route2']
            ],
            'action12' => [
                'label' => 'Label12',
                'applications' => ['backend'],
                'entities' => ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'],
            ],
            'action13' => [
                'label' => 'Label13',
                'for_all_entities' => true
            ],
            'action14' => [
                'label' => 'Label14',
                'for_all_entities' => true,
                'entities' => ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'],
                'exclude_entities' => ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1']
            ],
            'action15' => [
                'label' => 'Label15',
                'entities' => ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity3']
            ],
            'action16' => [
                'label' => 'Label17Substituted15',
                'substitute_action' => 'action15'
            ],
            'action17' => [
                'label' => 'Label17',
                'substitute_action' => 'unreachableAction',
                'groups' => ['group5']
            ],
            'action18' => [
                'label' => 'Label18',
                'for_all_entities' => true,
                'groups' => ['limited']
            ],
            'action19' => [
                'label' => 'Label18 Specific Entity Replacement',
                'substitute_action' => 'action18',
                'entities' => ['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity3'],
                'groups' => ['limited']
            ]
        ];
    }
}
