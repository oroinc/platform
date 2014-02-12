<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Data;

use Oro\Bundle\EntityMergeBundle\Data\EntityDataFactory;

class EntityDataFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityDataFactory $target
     */
    private $target;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $metadata;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject[]
     */
    private $entities = array();

    /**
     * @var array
     */
    private $fieldsMetadata = array();

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $firstEntity;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $secondEntity;

    /**
     * @var string $entitiesClassName Class name for entities
     */
    private $entitiesClassName;

    public function setUp()
    {
        $this->entitiesClassName = 'testClassNameForEntity';
        $this->firstEntity = $this->getMockBuilder('stdClass')->setMockClassName($this->entitiesClassName)->getMock();
        $this->secondEntity = $this->getMockBuilder('stdClass')->setMockClassName(
            $this->entitiesClassName
        )->getMock();

        $this->entities[] = $this->firstEntity;
        $this->entities[] = $this->secondEntity;

        $this->metadataFactory = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\MetadataFactory')
            ->disableOriginalConstructor()->getMock();

        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper')
            ->disableOriginalConstructor()->getMock();

        $this->metadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()->getMock();

        $this->metadata->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue($this->entitiesClassName));

        $this->metadata->expects($this->any())
            ->method('getFieldsMetadata')
            ->will($this->returnValue($this->fieldsMetadata));

        $this->metadataFactory->expects($this->any())
            ->method('createMergeMetadata')
            ->with($this->entitiesClassName)
            ->will($this->returnValue($this->metadata));

        $eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()->getMock();

        $this->target = new EntityDataFactory(
            $this->metadataFactory,
            $this->doctrineHelper,
            $eventDispatcher
        );
    }

    public function testCreateEntityDataShouldReturnCorrectEntities()
    {
        $result = $this->target->createEntityData($this->entitiesClassName, $this->entities);
        $this->assertEquals($result->getClassName(), $this->entitiesClassName);
        $this->assertEquals($this->metadata, $result->getMetadata());
        $expected = $this->entities;
        $this->assertEquals($result->getEntities(), $expected);
    }

    public function testCreateEntityDataByIdsShouldCallCreateEntityDataWithCorrectData()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntitiesByIds')
            ->with(
                $this->equalTo($this->entitiesClassName),
                $this->callback(
                    function ($params) {
                        return $params[0] == '12' && $params[1] == '88';
                    }
                )
            )
            ->will($this->returnValue($this->entities));

        $expected = $this->entities;

        $result = $this->target->createEntityDataByIds($this->entitiesClassName, array('12', '88'));

        $this->assertEquals($result->getEntities(), $expected);
    }
}
