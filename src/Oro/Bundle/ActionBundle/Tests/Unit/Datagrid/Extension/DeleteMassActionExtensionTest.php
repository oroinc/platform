<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Datagrid\Extension\DeleteMassActionExtension;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;

class DeleteMassActionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityClassResolver */
    protected $entityClassResolver;

    /** @var GridConfigurationHelper */
    protected $gridConfigurationHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|OperationRegistry */
    protected $registry;

    /** @var DeleteMassActionExtension */
    protected $extension;

    /** @var ContextHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();

        $this->entityClassResolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->gridConfigurationHelper = new GridConfigurationHelper($this->entityClassResolver);

        $this->registry = $this->getMockBuilder(OperationRegistry::class)->disableOriginalConstructor()->getMock();
        $this->contextHelper = $this->getMockBuilder(ContextHelper::class)->disableOriginalConstructor()->getMock();

        $this->extension = new DeleteMassActionExtension(
            $this->doctrineHelper,
            $this->gridConfigurationHelper,
            $this->registry,
            $this->contextHelper
        );
    }

    protected function tearDown()
    {
        unset(
            $this->extension,
            $this->doctrineHelper,
            $this->gridConfigurationHelper,
            $this->entityClassResolver,
            $this->registry
        );
    }

    public function testSetGroups()
    {
        $data = ['test_group'];

        $this->extension->setGroups($data);

        $this->assertAttributeEquals($data, 'groups', $this->extension);
    }

    /**
     * @dataProvider isApplicableDataProvider
     *
     * @param Operation $operation
     * @param ActionData $actionData
     * @param bool $expected
     */
    public function testIsApplicable(Operation $operation = null, ActionData $actionData, $expected)
    {
        $this->registry->expects($this->once())
            ->method('findByName')
            ->with(
                DeleteMassActionExtension::OPERATION_NAME
            )
            ->willReturn($operation);

        $context = [
            'entityClass' => 'TestEntity',
            'datagrid' => 'test-grid',
            'group' => ['test_group']
        ];

        if ($operation) {
            $this->contextHelper->expects($this->once())
                ->method('getActionData')
                ->with($context)
                ->willReturn($actionData);
        } else {
            $this->contextHelper->expects($this->never())->method('getActionData');
        }

        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->with('test_entity_table')
            ->willReturn('TestEntity');

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->with('TestEntity', false)
            ->willReturn('id');

        $this->extension->setGroups(['test_group']);

        $this->assertEquals($expected, $this->extension->isApplicable($this->getDatagridConfiguration()));
    }

    /**
     * @return array
     */
    public function isApplicableDataProvider()
    {
        $actionData = new ActionData([
            'entityClass' => 'TestEntity',
            'datagrid' => 'test-grid',
            'group' => ['test_group']
        ]);

        $operationAvailable = $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();
        $operationAvailable->expects($this->once())
            ->method('isAvailable')->with($actionData)->willReturn(true);

        $operationNotAvailable = $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();
        $operationNotAvailable->expects($this->once())
            ->method('isAvailable')->with($actionData)->willReturn(false);

        return [
            [
                'operation' => null,
                'actionData' => $actionData,
                'expected' => false,
            ],
            [
                'operation' => $operationAvailable,
                'actionData' => $actionData,
                'expected' => true
            ],
            [
                'operation' => $operationNotAvailable,
                'actionData' => $actionData,
                'expected' => false
            ]
        ];
    }

    /**
     * @return DatagridConfiguration
     */
    private function getDatagridConfiguration()
    {
        return DatagridConfiguration::createNamed(
            'test-grid',
            [
                'source' => [
                    'type' => OrmDatasource::TYPE,
                    'query' => [
                        'from' => [
                            [
                                'table' => 'test_entity_table',
                                'alias' => 'test_entity'
                            ]
                        ]
                    ]
                ],
                'actions' => ['delete' => []]
            ]
        );
    }
}
