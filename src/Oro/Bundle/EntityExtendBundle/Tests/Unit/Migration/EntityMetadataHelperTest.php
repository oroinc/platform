<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity;
use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class EntityMetadataHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityMetadataHelper
     */
    private $helper;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry
     */
    private $doctrine;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->helper = new EntityMetadataHelper($this->doctrine);
    }

    public function testGetEntityClassesByTableName()
    {
        $testEntityMetadata = new ClassMetadataInfo('Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity');
        $testEntityMetadata->table['name'] = 'acme_test';
        $testEntity2Metadata = new ClassMetadataInfo('Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity2');
        $testEntity2Metadata->table['name'] = 'acme_test';

        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue([$testEntityMetadata, $testEntity2Metadata]));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));

        $this->doctrine->expects($this->once())
            ->method('getManagerNames')
            ->will($this->returnValue(['default' => 'service.default']));
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with($this->equalTo('default'))
            ->will($this->returnValue($em));

        $this->assertEquals(
            [
                'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity',
                'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity2',
            ],
            $this->helper->getEntityClassesByTableName('acme_test')
        );
    }

    public function testGetTableNameByEntityClass()
    {
        $metadata = new ClassMetadataInfo('Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity');
        $metadata->table['name'] = 'acme_test';

        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue([$metadata]));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));

        $this->doctrine->expects($this->once())
            ->method('getManagerNames')
            ->will($this->returnValue(['default' => 'service.default']));
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with($this->equalTo('default'))
            ->will($this->returnValue($em));

        $this->assertEquals(
            'acme_test',
            $this->helper->getTableNameByEntityClass('Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity')
        );
    }

    public function testGetFieldNameByColumnName()
    {
        $metadata = new ClassMetadataInfo('Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity');
        $metadata->table['name'] = 'acme_test';
        $metadata->fieldNames['name_column'] = 'name_field';

        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory->expects($this->once())
            ->method('getAllMetadata')
            ->will($this->returnValue([$metadata]));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));
        $em->expects($this->once())
            ->method('getClassMetadata')
            ->with('Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity')
            ->will($this->returnValue($metadata));

        $this->doctrine->expects($this->once())
            ->method('getManagerNames')
            ->will($this->returnValue(['default' => 'service.default']));
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with($this->equalTo('default'))
            ->will($this->returnValue($em));
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with('Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity')
            ->will($this->returnValue($em));

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

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $this->doctrine->expects($this->once())
            ->method('getManagerNames')
            ->willReturn(['default' => 'service.default']);
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with($this->equalTo('default'))
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

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        $this->doctrine->expects($this->once())
            ->method('getManagerNames')
            ->willReturn(['default' => 'service.default']);
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with($this->equalTo('default'))
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
