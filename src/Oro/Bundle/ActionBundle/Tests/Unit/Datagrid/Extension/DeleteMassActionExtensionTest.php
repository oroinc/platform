<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Datagrid\Extension\DeleteMassActionExtension;
use Oro\Bundle\ActionBundle\Model\OperationManager;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
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

    /** @var \PHPUnit_Framework_MockObject_MockObject|OperationManager */
    protected $manager;

    /** @var DeleteMassActionExtension */
    protected $extension;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)->disableOriginalConstructor()->getMock();

        $this->entityClassResolver = $this->getMockBuilder(EntityClassResolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->gridConfigurationHelper = new GridConfigurationHelper($this->entityClassResolver);

        $this->manager = $this->getMockBuilder(OperationManager::class)->disableOriginalConstructor()->getMock();

        $this->extension = new DeleteMassActionExtension(
            $this->doctrineHelper,
            $this->gridConfigurationHelper,
            $this->manager
        );
    }

    protected function tearDown()
    {
        unset(
            $this->extension,
            $this->doctrineHelper,
            $this->gridConfigurationHelper,
            $this->entityClassResolver,
            $this->manager
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
     * @param bool $hasOperation
     */
    public function testIsApplicable($hasOperation)
    {
        $this->manager->expects($this->once())
            ->method('hasOperation')
            ->with(
                DeleteMassActionExtension::OPERATION_NAME,
                [
                    'entityClass' => 'TestEntity',
                    'datagrid' => 'test-grid',
                    'group' => ['test_group']
                ]
            )
            ->willReturn($hasOperation);

        $this->entityClassResolver->expects($this->any())
            ->method('getEntityClass')
            ->with('test_entity_table')
            ->willReturn('TestEntity');

        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifierFieldName')
            ->with('TestEntity', false)
            ->willReturn('id');

        $this->extension->setGroups(['test_group']);

        $this->assertEquals($hasOperation, $this->extension->isApplicable($this->getDatagridCinfiguration()));
    }

    /**
     * @return array
     */
    public function isApplicableDataProvider()
    {
        return [
            ['hasOperation' => true],
            ['hasOperation' => false]
        ];
    }

    /**
     * @return DatagridConfiguration
     */
    private function getDatagridCinfiguration()
    {
        return DatagridConfiguration::createNamed(
            'test-grid',
            [
                'source' => [
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
