<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityData;
use Oro\Bundle\ApiBundle\Processor\Shared\PersistIncludedEntities;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class PersistIncludedEntitiesTest extends FormProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    /** @var PersistIncludedEntities */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

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

    public function testProcessWhenIncludedEntitiesCollectionDoesNotExist()
    {
        $this->context->setIncludedData([['key' => 'val']]);
        $this->processor->process($this->context);
    }

    public function testProcessWhenIncludedEntitiesCollectionIsEmpty()
    {
        $this->context->setIncludedData([['key' => 'val']]);
        $this->context->setIncludedEntities(new IncludedEntityCollection());
        $this->processor->process($this->context);
    }

    public function testProcessForNewIncludedObject()
    {
        $object = new \stdClass();
        $objectClass = 'Test\Class';
        $isExistingObject = false;

        $includedEntities = new IncludedEntityCollection();
        $includedEntities->add(
            $object,
            $objectClass,
            'id',
            new IncludedEntityData('/included/0', 0, $isExistingObject)
        );

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($object), false)
            ->willReturn(null);

        $this->context->setIncludedData([['key' => 'val']]);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);
    }

    public function testProcessForExistingIncludedObject()
    {
        $object = new \stdClass();
        $objectClass = 'Test\Class';
        $isExistingObject = true;

        $includedEntities = new IncludedEntityCollection();
        $includedEntities->add(
            $object,
            $objectClass,
            'id',
            new IncludedEntityData('/included/0', 0, $isExistingObject)
        );

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setIncludedData([['key' => 'val']]);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);
    }

    public function testProcessForNewIncludedEntity()
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $isExistingEntity = false;

        $includedEntities = new IncludedEntityCollection();
        $includedEntities->add(
            $entity,
            $entityClass,
            'id',
            new IncludedEntityData('/included/0', 0, $isExistingEntity)
        );

        $em = $this->createMock(EntityManager::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityManager')
            ->with(self::identicalTo($entity), false)
            ->willReturn($em);
        $em->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($entity));

        $this->context->setIncludedData([['key' => 'val']]);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);
    }

    public function testProcessForExistingIncludedEntity()
    {
        $entity = new \stdClass();
        $entityClass = 'Test\Class';
        $isExistingEntity = true;

        $includedEntities = new IncludedEntityCollection();
        $includedEntities->add(
            $entity,
            $entityClass,
            'id',
            new IncludedEntityData('/included/0', 0, $isExistingEntity)
        );

        $this->doctrineHelper->expects(self::never())
            ->method('getEntityManager');

        $this->context->setIncludedData([['key' => 'val']]);
        $this->context->setIncludedEntities($includedEntities);
        $this->processor->process($this->context);
    }
}
