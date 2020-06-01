<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Datagrid\Extension\DeleteMassActionExtension;
use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\Criteria\OperationFindCriteria;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use PHPUnit\Framework\MockObject\MockObject;

class DeleteMassActionExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var MockObject|EntityClassResolver */
    protected $entityClassResolver;

    /** @var MockObject|OperationRegistry */
    protected $registry;

    /** @var DeleteMassActionExtension */
    protected $extension;

    /** @var ContextHelper|MockObject */
    protected $contextHelper;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->registry = $this->createMock(OperationRegistry::class);
        $this->contextHelper = $this->createMock(ContextHelper::class);

        $this->extension = new class(
            $this->doctrineHelper,
            $this->entityClassResolver,
            $this->registry,
            $this->contextHelper
        ) extends DeleteMassActionExtension {
            public function xgetGroups(): array
            {
                return $this->groups;
            }
        };

        $this->extension->setParameters(new ParameterBag());
    }

    protected function tearDown(): void
    {
        unset(
            $this->extension,
            $this->doctrineHelper,
            $this->entityClassResolver,
            $this->registry
        );
    }

    public function testSetGroups()
    {
        $data = ['test_group'];

        $this->extension->setGroups($data);

        static::assertEquals($data, $this->extension->xgetGroups());
    }

    /**
     * @dataProvider isApplicableDataProvider
     *
     * @param ActionData $actionData
     * @param Operation $operation
     * @param bool $expected
     */
    public function testIsApplicable(ActionData $actionData, Operation $operation = null, $expected = false)
    {
        $this->registry->expects($this->once())
            ->method('findByName')
            ->with(
                DeleteMassActionExtension::OPERATION_NAME,
                new OperationFindCriteria('TestEntity', null, 'test-grid', ['test_group'])
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
        $operationAvailable->expects($this->any())
            ->method('getDefinition')->willReturn(new OperationDefinition());

        $operationNotAvailable = $this->getMockBuilder(Operation::class)->disableOriginalConstructor()->getMock();
        $operationNotAvailable->expects($this->once())
            ->method('isAvailable')->with($actionData)->willReturn(false);
        $operationNotAvailable->expects($this->any())
            ->method('getDefinition')->willReturn(new OperationDefinition());

        return [
            [
                'actionData' => $actionData,
                'operation' => null
            ],
            [
                'actionData' => $actionData,
                'operation' => $operationAvailable,
                'expected' => true,
            ],
            [
                'actionData' => $actionData,
                'operation' => $operationNotAvailable
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
