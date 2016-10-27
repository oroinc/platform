<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Collection\IncludedObjectCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedObjectData;
use Oro\Bundle\ApiBundle\Processor\Shared\PersistIncludedEntities;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class PersistIncludedEntitiesTest extends FormProcessorTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrineHelper;

    /** @var PersistIncludedEntities */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new PersistIncludedEntities($this->doctrineHelper);
    }

    public function testProcessWhenIncludedDataDoesNotExist()
    {
        $this->processor->process($this->context);
    }

    public function testProcessWhenIncludedDataIsEmpty()
    {
        $this->context->setIncludedData([]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenIncludedObjectsCollectionDoesNotExist()
    {
        $this->context->setIncludedData([['key' => 'val']]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenIncludedObjectsCollectionIsEmpty()
    {
        $this->context->setIncludedData([['key' => 'val']]);
        $this->context->setIncludedObjects(new IncludedObjectCollection());
        $this->processor->process($this->context);
    }

    public function testProcessForNewIncludedObject()
    {
        $object = new \stdClass();
        $objectClass = 'TestClass';
        $isExistingObject = false;

        $includedObjects = new IncludedObjectCollection();
        $includedObjects->add(
            $object,
            $objectClass,
            'id',
            new IncludedObjectData('/included/0', 0, $isExistingObject)
        );

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($object), false)
            ->willReturn(null);

        $this->context->setIncludedData([['key' => 'val']]);
        $this->context->setIncludedObjects($includedObjects);
        $this->processor->process($this->context);
    }

    public function testProcessForExistingIncludedObject()
    {
        $object = new \stdClass();
        $objectClass = 'TestClass';
        $isExistingObject = true;

        $includedObjects = new IncludedObjectCollection();
        $includedObjects->add(
            $object,
            $objectClass,
            'id',
            new IncludedObjectData('/included/0', 0, $isExistingObject)
        );

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setIncludedData([['key' => 'val']]);
        $this->context->setIncludedObjects($includedObjects);
        $this->processor->process($this->context);
    }

    public function testProcessForNewIncludedEntity()
    {
        $object = new \stdClass();
        $objectClass = 'TestClass';
        $isExistingObject = false;

        $includedObjects = new IncludedObjectCollection();
        $includedObjects->add(
            $object,
            $objectClass,
            'id',
            new IncludedObjectData('/included/0', 0, $isExistingObject)
        );

        $em = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($object), false)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($object));

        $this->context->setIncludedData([['key' => 'val']]);
        $this->context->setIncludedObjects($includedObjects);
        $this->processor->process($this->context);
    }

    public function testProcessForExistingIncludedEntity()
    {
        $object = new \stdClass();
        $objectClass = 'TestClass';
        $isExistingObject = true;

        $includedObjects = new IncludedObjectCollection();
        $includedObjects->add(
            $object,
            $objectClass,
            'id',
            new IncludedObjectData('/included/0', 0, $isExistingObject)
        );

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setIncludedData([['key' => 'val']]);
        $this->context->setIncludedObjects($includedObjects);
        $this->processor->process($this->context);
    }
}
