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
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteMassActionExtensionTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private EntityClassResolver&MockObject $entityClassResolver;
    private OperationRegistry&MockObject $registry;
    private ContextHelper&MockObject $contextHelper;
    private DeleteMassActionExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->entityClassResolver = $this->createMock(EntityClassResolver::class);
        $this->registry = $this->createMock(OperationRegistry::class);
        $this->contextHelper = $this->createMock(ContextHelper::class);

        $this->extension = new DeleteMassActionExtension(
            $this->doctrineHelper,
            $this->entityClassResolver,
            $this->registry,
            $this->contextHelper
        );
        $this->extension->setParameters(new ParameterBag());
    }

    public function testSetGroups(): void
    {
        $data = ['test_group'];

        $this->extension->setGroups($data);

        self::assertEquals($data, ReflectionUtil::getPropertyValue($this->extension, 'groups'));
    }

    /**
     * @dataProvider isApplicableDataProvider
     */
    public function testIsApplicable(ActionData $actionData, ?Operation $operation = null, bool $expected = false): void
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
            $this->contextHelper->expects($this->never())
                ->method('getActionData');
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

    public function isApplicableDataProvider(): array
    {
        $actionData = new ActionData([
            'entityClass' => 'TestEntity',
            'datagrid' => 'test-grid',
            'group' => ['test_group']
        ]);

        $operationAvailable = $this->createMock(Operation::class);
        $operationAvailable->expects($this->once())
            ->method('isAvailable')
            ->with($actionData)
            ->willReturn(true);
        $operationAvailable->expects($this->any())
            ->method('getDefinition')
            ->willReturn(new OperationDefinition());

        $operationNotAvailable = $this->createMock(Operation::class);
        $operationNotAvailable->expects($this->once())
            ->method('isAvailable')
            ->with($actionData)
            ->willReturn(false);
        $operationNotAvailable->expects($this->any())
            ->method('getDefinition')
            ->willReturn(new OperationDefinition());

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

    private function getDatagridConfiguration(): DatagridConfiguration
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
