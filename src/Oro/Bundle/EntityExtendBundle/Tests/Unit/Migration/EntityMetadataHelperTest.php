<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
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

    protected function setUp()
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
}
