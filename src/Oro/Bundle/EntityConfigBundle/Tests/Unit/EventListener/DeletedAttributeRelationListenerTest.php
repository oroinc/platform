<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\EventListener\DeletedAttributeRelationListener;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProviderInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\TestUtils\ORM\Mocks\UnitOfWork;

class DeletedAttributeRelationListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $messageProducer;

    /**
     * @var DeletedAttributeProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $deletedAttributeProvider;

    /**
     * @var DeletedAttributeRelationListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->deletedAttributeProvider = $this->createMock(DeletedAttributeProviderInterface::class);
        $this->listener = new DeletedAttributeRelationListener(
            $this->messageProducer,
            $this->deletedAttributeProvider
        );
    }

    public function testOnFlush()
    {
        $attributeFamilyId = 333;
        $movedAttributeId = 777;
        $deletedAttributeId = 888;
        $attributeFamily = $this->getFilledAttributeFamily($attributeFamilyId, $movedAttributeId);
        
        $uow = new UnitOfWork();
        $uow->addDeletion($this->getFilledAttributeGroupRelation($attributeFamily, $movedAttributeId));
        $uow->addDeletion($this->getFilledAttributeGroupRelation($attributeFamily, $deletedAttributeId));
        $uow->addDeletion(new \stdClass());

        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        
        $this->deletedAttributeProvider->expects($this->once())
            ->method('getAttributesByIds')
            ->with([$deletedAttributeId])
            ->willReturn([new FieldConfigModel('field_name')]);

        $event = new OnFlushEventArgs($entityManager);
        $this->listener->onFlush($event);

        $reflectionProperty = $this->getDeletedAttributesReflectionProperty();
        $this->assertEquals(
            [$attributeFamilyId => ['fieldName']],
            $reflectionProperty->getValue($this->listener)
        );
    }
    
    public function testPostFlush()
    {
        $emptyFamilyId = 1;
        $filledFamilyId = 2;
        $filledFamilyAttributeNames = ['name'];
        $topicName = 'topic';

        $reflectionProperty = $this->getDeletedAttributesReflectionProperty();
        $reflectionProperty->setValue(
            $this->listener,
            [
                $emptyFamilyId => [],
                $filledFamilyId => $filledFamilyAttributeNames,
            ]
        );
        
        $this->listener->setTopic($topicName);
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(
                $topicName,
                new Message([
                    'attributeFamilyId' => $filledFamilyId,
                    'attributeNames' => $filledFamilyAttributeNames,
                ], MessagePriority::NORMAL)
            );

        $this->listener->postFlush();
        $this->assertEmpty($reflectionProperty->getValue($this->listener));
    }

    /**
     * @param int $attributeFamilyId
     * @param int $movedAttributeId
     * @return AttributeFamily
     */
    protected function getFilledAttributeFamily($attributeFamilyId, $movedAttributeId)
    {
        $attributeRelation = new AttributeGroupRelation();
        $attributeRelation->setEntityConfigFieldId($movedAttributeId);

        $attributeGroup = new AttributeGroup();
        $attributeGroup->addAttributeRelation($attributeRelation);

        /** @var AttributeFamily $attributeFamily */
        $attributeFamily = $this->getEntity(AttributeFamily::class, ['id' => $attributeFamilyId]);
        $attributeFamily->addAttributeGroup($attributeGroup);

        return $attributeFamily;
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @param int $attributeId
     * @return AttributeGroupRelation
     */
    protected function getFilledAttributeGroupRelation(AttributeFamily $attributeFamily, $attributeId)
    {
        $attributeGroup = new AttributeGroup();
        $attributeGroup->setAttributeFamily($attributeFamily);

        $attributeGroupRelation = new AttributeGroupRelation();
        $attributeGroupRelation->setEntityConfigFieldId($attributeId);
        $attributeGroupRelation->setAttributeGroup($attributeGroup);
        
        return $attributeGroupRelation;
    }

    /**
     * @return \ReflectionProperty
     */
    protected function getDeletedAttributesReflectionProperty()
    {
        $reflectionClass = new \ReflectionClass($this->listener);
        $reflectionProperty = $reflectionClass->getProperty('deletedAttributes');
        $reflectionProperty->setAccessible(true);
        
        return $reflectionProperty;
    }
}
