<?php
namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Provider\DeletedAttributeProviderInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class DeletedAttributeRelationListener
{
    /**
     * @var MessageProducerInterface
     */
    protected $messageProducer;

    /**
     * @var DeletedAttributeProviderInterface
     */
    protected $deletedAttributeProvider;

    /**
     * @var string
     */
    protected $topic = '';

    /**
     * @var array
     */
    protected $deletedAttributes = [];

    /**
     * @param MessageProducerInterface $messageProducer
     * @param DeletedAttributeProviderInterface $deletedAttributeProvider
     */
    public function __construct(
        MessageProducerInterface $messageProducer,
        DeletedAttributeProviderInterface $deletedAttributeProvider
    ) {
        $this->messageProducer = $messageProducer;
        $this->deletedAttributeProvider = $deletedAttributeProvider;
    }

    /**
     * @param string $topic
     */
    public function setTopic($topic)
    {
        $this->topic = (string) $topic;
    }

    /**
     * @param OnFlushEventArgs $eventArgs
     */
    public function onFlush(OnFlushEventArgs $eventArgs)
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
                $attribute = Inflector::camelize($attribute->getFieldName());
            }
            
            $this->deletedAttributes[$attributeFamilyId] = $attributes;
        }
    }

    public function postFlush()
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

    /**
     * @param AttributeFamily $attributeFamily
     * @param int $attributeId
     * @return bool
     */
    protected function checkIsDeleted(AttributeFamily $attributeFamily, $attributeId)
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
}
