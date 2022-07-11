<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;

class EntityMetadataHelperTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var EntityMetadataHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->helper = new EntityMetadataHelper($this->doctrine);
    }

    public function testGetEntityClassesByTableName()
    {
        $testEntityMetadata = new ClassMetadataInfo(TestEntity::class);
        $testEntityMetadata->table['name'] = 'acme_test';
        $testEntity2Metadata = new ClassMetadataInfo('Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity2');
        $testEntity2Metadata->table['name'] = 'acme_test';

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([$testEntityMetadata, $testEntity2Metadata]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $this->doctrine->expects($this->once())
            ->method('getManagerNames')
            ->willReturn(['default' => 'service.default']);
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with('default')
            ->willReturn($em);

        $this->assertEquals(
            [
                TestEntity::class,
                'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity2',
            ],
            $this->helper->getEntityClassesByTableName('acme_test')
        );
    }

    public function testGetTableNameByEntityClass()
    {
        $metadata = new ClassMetadataInfo(TestEntity::class);
        $metadata->table['name'] = 'acme_test';

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([$metadata]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $this->doctrine->expects($this->once())
            ->method('getManagerNames')
            ->willReturn(['default' => 'service.default']);
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with('default')
            ->willReturn($em);

        $this->assertEquals(
            'acme_test',
            $this->helper->getTableNameByEntityClass(TestEntity::class)
        );
    }

    public function testGetFieldNameByColumnName()
    {
        $metadata = new ClassMetadataInfo(TestEntity::class);
        $metadata->table['name'] = 'acme_test';
        $metadata->fieldNames['name_column'] = 'name_field';

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([$metadata]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with(TestEntity::class)
            ->willReturn($metadata);

        $this->doctrine->expects($this->once())
            ->method('getManagerNames')
            ->willReturn(['default' => 'service.default']);
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with('default')
            ->willReturn($em);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(TestEntity::class)
            ->willReturn($em);

        $this->assertEquals(
            'name_field',
            $this->helper->getFieldNameByColumnName('acme_test', 'name_column')
        );
    }

    public function testGetEntityColumnsByEntityClass(): void
    {
        $metadata = new ClassMetadataInfo(TestEntity::class);
        $metadata->table['name'] = 'acme_test';
        $metadata->fieldNames = ['test_column1' => 'testField1', 'test_column2' => 'testField2'];
        $metadata->discriminatorColumn['name'] = 'test_column3';
        $metadata->associationMappings = [
            'testField4' => [
                'fieldName' => 'testField4',
                'joinColumns' => [
                    [
                        'name' => 'test_column4',
                    ]
                ],
            ],
            'testField5' => [
                'fieldName' => 'testField5',
                'joinColumns' => [
                    [
                        'name' => 'test_column5',
                    ]
                ],
            ],
            'testField6' => [
                'fieldName' => 'testField6',
            ],
        ];

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([$metadata]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $this->doctrine->expects($this->once())
            ->method('getManagerNames')
            ->willReturn(['default' => 'service.default']);
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with('default')
            ->willReturn($em);

        $this->assertEquals(
            ['test_column1', 'test_column2', 'test_column3', 'test_column4', 'test_column5'],
            $this->helper->getEntityColumnsByEntityClass(TestEntity::class)
        );
    }

    public function testIsEntityClassContainsColumn(): void
    {
        $metadata = new ClassMetadataInfo(TestEntity::class);
        $metadata->table['name'] = 'acme_test';
        $metadata->fieldNames = ['test_column1' => 'testField1', 'test_column2' => 'testField2'];
        $metadata->discriminatorColumn['name'] = 'test_column3';
        $metadata->associationMappings = [
            'testField4' => [
                'fieldName' => 'testField4',
                'joinColumns' => [
                    [
                        'name' => 'test_column4',
                    ]
                ],
            ],
            'testField5' => [
                'fieldName' => 'testField5',
                'joinColumns' => [
                    [
                        'name' => 'test_column5',
                    ]
                ],
            ],
            'testField6' => [
                'fieldName' => 'testField6',
            ],
        ];

        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->willReturn([$metadata]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $this->doctrine->expects($this->once())
            ->method('getManagerNames')
            ->willReturn(['default' => 'service.default']);
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with('default')
            ->willReturn($em);

        $this->assertTrue($this->helper->isEntityClassContainsColumn(TestEntity::class, 'test_column1'));
        $this->assertTrue($this->helper->isEntityClassContainsColumn(TestEntity::class, 'test_column2'));
        $this->assertTrue($this->helper->isEntityClassContainsColumn(TestEntity::class, 'test_column3'));
        $this->assertTrue($this->helper->isEntityClassContainsColumn(TestEntity::class, 'test_column4'));
        $this->assertTrue($this->helper->isEntityClassContainsColumn(TestEntity::class, 'test_column5'));
        $this->assertFalse($this->helper->isEntityClassContainsColumn(TestEntity::class, 'test_column6'));
        $this->assertFalse($this->helper->isEntityClassContainsColumn(TestEntity::class, 'some_random_column_name'));
        $this->assertTrue($this->helper->isEntityClassContainsColumn(\stdClass::class, 'test_column5'));
    }
}
