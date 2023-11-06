<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProviderInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * Doctrine's listener of 'onFlush', 'postFlush' events
 * to produce MQ message which processor must delete attribute relations.
 */
class DeletedAttributeRelationListener
{
    protected MessageProducerInterface $messageProducer;
    protected DeletedAttributeProviderInterface $deletedAttributeProvider;
    protected string $topic = '';
    protected array $deletedAttributes = [];

    public function __construct(
        MessageProducerInterface $messageProducer,
        DeletedAttributeProviderInterface $deletedAttributeProvider
    ) {
        $this->messageProducer = $messageProducer;
        $this->deletedAttributeProvider = $deletedAttributeProvider;
    }

    public function setTopic(string $topic): void
    {
        $this->topic = $topic;
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $uow = $eventArgs->getEntityManager()->getUnitOfWork();

        foreach ($uow->getScheduledEntityDeletions() as $attributeRelation) {
            if (!$attributeRelation instanceof AttributeGroupRelation) {
                continue;
            }
            $attributeFamily = $attributeRelation->getAttributeGroup()->getAttributeFamily();

            if ($this->checkIsDeleted($attributeFamily, $attributeRelation->getEntityConfigFieldId())) {
                $this->deletedAttributes[$attributeFamily->getId()][] = $attributeRelation->getEntityConfigFieldId();
            }
        }

        foreach ($this->deletedAttributes as $attributeFamilyId => $attributeIds) {
            $attributes = $this->deletedAttributeProvider->getAttributesByIds($attributeIds);
            foreach ($attributes as &$attribute) {
                $attribute = $this->getAttributeName($attribute);
            }
            unset($attribute);

            $this->deletedAttributes[$attributeFamilyId] = $attributes;
        }
    }

    public function postFlush(): void
    {
        foreach ($this->deletedAttributes as $attributeFamilyId => $attributeNames) {
            if (!$attributeNames) {
                continue;
            }

            $this->messageProducer->send(
                $this->topic,
                new Message(
                    ['attributeFamilyId' => $attributeFamilyId, 'attributeNames' => $attributeNames],
                    MessagePriority::NORMAL
                )
            );
        }

        $this->deletedAttributes = [];
    }

    protected function checkIsDeleted(AttributeFamily $attributeFamily, int $attributeId): bool
    {
        foreach ($attributeFamily->getAttributeGroups() as $attributeGroup) {
            foreach ($attributeGroup->getAttributeRelations() as $attributeGroupRelation) {
                if ($attributeGroupRelation->getEntityConfigFieldId() === $attributeId) {
                    return false;
                }
            }
        }

        return true;
    }

    protected function getAttributeName(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName();
    }
}
