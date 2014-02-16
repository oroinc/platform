<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Data;

use Oro\Bundle\EntityMergeBundle\Data\EntityDataFactory;
use Oro\Bundle\EntityMergeBundle\MergeEvents;

class EntityDataFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityDataFactory
     */
    private $factory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataRegistry;

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
    private $eventDispatcher;

    /**
     * @var string $entitiesClassName Class name for entities
     */
    private $entitiesClassName;

    protected function setUp()
    {
        $this->entitiesClassName = 'testClassNameForEntity';

        $this->entities[] = $this
            ->getMockBuilder('stdClass')
            ->setMockClassName($this->entitiesClassName)
            ->getMock();

        $this->entities[] = $this
            ->getMockBuilder('stdClass')
            ->setMockClassName($this->entitiesClassName)
            ->getMock();

        $this->metadataRegistry = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\MetadataRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Doctrine\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadata = $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadata->expects($this->any())
            ->method('getClassName')
            ->will($this->returnValue($this->entitiesClassName));

        $this->metadata->expects($this->any())
            ->method('getFieldsMetadata')
            ->will($this->returnValue($this->fieldsMetadata));

        $this->metadataRegistry
            ->expects($this->any())
            ->method('getEntityMetadata')
            ->with($this->entitiesClassName)
            ->will($this->returnValue($this->metadata));

        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->factory = new EntityDataFactory(
            $this->metadataRegistry,
            $this->doctrineHelper,
            $this->eventDispatcher
        );
    }

    public function testCreateEntityData()
    {
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                MergeEvents::CREATE_ENTITY_DATA,
                $this->callback(
                    function ($event) {
                        self::assertInstanceOf('Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent', $event);
                        self::assertInstanceOf('Oro\Bundle\EntityMergeBundle\Data\EntityData', $event->getEntityData());
                        return true;
                    }
                )
            );

        $result = $this->factory->createEntityData($this->entitiesClassName, $this->entities);
        $this->assertEquals($result->getClassName(), $this->entitiesClassName);
        $this->assertEquals($this->metadata, $result->getMetadata());
        $this->assertEquals($this->entities, $result->getEntities());
    }

    public function testCreateEntityDataByIds()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntitiesByIds')
            ->with(
                $this->entitiesClassName,
                $this->callback(
                    function ($params) {
                        return $params[0] == '12' && $params[1] == '88';
                    }
                )
            )
            ->will($this->returnValue($this->entities));

        $result = $this->factory->createEntityDataByIds($this->entitiesClassName, array('12', '88'));

        $this->assertEquals($this->entities, $result->getEntities());
    }
}
