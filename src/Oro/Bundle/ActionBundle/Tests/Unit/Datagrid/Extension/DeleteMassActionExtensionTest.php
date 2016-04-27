<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Datagrid\Extension;

use Oro\Bundle\ActionBundle\Datagrid\Extension\DeleteMassActionExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class DeleteMassActionExtensionTest extends AbstractExtensionTest
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var DeleteMassActionExtension */
    protected $extension;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new DeleteMassActionExtension(
            $this->doctrineHelper,
            $this->gridConfigurationHelper,
            $this->manager
        );
    }

    protected function tearDown()
    {
        unset($this->extension, $this->doctrineHelper);

        parent::tearDown();
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
     * @param array $actionsConfig
     * @param bool $expected
     */
    public function testIsApplicable(array $actionsConfig, $expected)
    {
        $this->manager->expects($this->once())
            ->method('getOperations')
            ->willReturn(['DELETE' => $this->createOperation('DELETE', true, [])]);

        $this->entityClassResolver->expects($this->exactly($actionsConfig ? 2 : 1))
            ->method('getEntityClass')
            ->with('test_entity_table')
            ->willReturn('TestEntity');

        $this->doctrineHelper->expects($this->exactly($actionsConfig ? 1 : 0))
            ->method('getSingleEntityIdentifierFieldName')
            ->with('TestEntity', false)
            ->willReturn('id');

        $this->assertEquals(
            $expected,
            $this->extension->isApplicable(
                DatagridConfiguration::createNamed(
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
                        'actions' => $actionsConfig
                    ]
                )
            )
        );
    }

    /**
     * @return array
     */
    public function isApplicableDataProvider()
    {
        return [
            [
                'actionsConfig' => ['delete' => []],
                'expected' => true
            ],
            [
                'actionsConfig' => [],
                'expected' => false
            ]
        ];
    }
}
