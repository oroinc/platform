<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityExtendBundle\Migration\EntityMetadataHelper;

class EntityMetadataHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityMetadataHelper
     */
    private $helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrine;

    protected function setUp()
    {
        $this->doctrine = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->helper = new EntityMetadataHelper($this->doctrine);
    }

    public function testGetEntityClassByTableName()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getTableName')
            ->will($this->returnValue('acme_test'));
        $metadata->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity'));

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
            ->method('getManagers')
            ->will($this->returnValue(array('default' => $em)));
        $this->doctrine->expects($this->once())
            ->method('getManager')
            ->with($this->equalTo('default'))
            ->will($this->returnValue($em));

        $this->assertEquals(
            'Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity',
            $this->helper->getEntityClassByTableName('acme_test')
        );
    }

    public function testGetTableNameByEntityClass()
    {
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getTableName')
            ->will($this->returnValue('acme_test'));
        $metadata->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity'));

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
            ->method('getManagers')
            ->will($this->returnValue(array('default' => $em)));
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
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->once())
            ->method('getTableName')
            ->will($this->returnValue('acme_test'));
        $metadata->expects($this->once())
            ->method('getFieldName')
            ->with('name_column')
            ->will($this->returnValue('name_field'));
        $metadata->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('Oro\Bundle\EntityBundle\Tests\Unit\ORM\Fixtures\TestEntity'));

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
            ->method('getManagers')
            ->will($this->returnValue(array('default' => $em)));
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
