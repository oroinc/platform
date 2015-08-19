<?php

namespace Oro\Bundle\FormBundle\Form\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class MultipleEntitySubscriber implements EventSubscriberInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => 'postSet',
            FormEvents::POST_SUBMIT   => 'postSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function postSet(FormEvent $event)
    {
        $form       = $event->getForm();
        $collection = $form->getData();
        $added      = $removed = [];

        // using array_values in order to prevent passing keys
        if ($collection instanceof PersistentCollection && $collection->isDirty()) {
            $added   = array_values($collection->getInsertDiff());
            $removed = array_values($collection->getDeleteDiff());
        } elseif ($collection instanceof ArrayCollection && $collection->count() > 0) {
            $added = array_values($collection->toArray());
        }

        $form->get('added')->setData($added);
        $form->get('removed')->setData($removed);
    }

    /**
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        $added   = $form->get('added')->getData();
        $removed = $form->get('removed')->getData();

        $parentData = $form->getParent()->getData();

        /** @var ClassMetadata $parentMetadata */
        $parentMetadata = $this->doctrineHelper->getEntityMetadata(ClassUtils::getClass($parentData));

        /** @var Collection $collection */
        $collection = $form->getData();

        foreach ($added as $relation) {
            $this->processRelation($parentMetadata, $relation, $parentData);
            $collection->add($relation);
        }

        foreach ($removed as $relation) {
            $this->processRelation($parentMetadata, $relation, null);
            $collection->removeElement($relation);
        }
    }

    /**
     * @param ClassMetadata $metadata
     * @param object $relation
     * @param mixed $value
     */
    protected function processRelation($metadata, $relation, $value)
    {
        foreach ($metadata->getAssociationMappings() as $mapping) {
            if (!is_array($mapping) || !isset($mapping['targetEntity'], $mapping['type'], $mapping['mappedBy'])) {
                continue;
            }
            if ($mapping['targetEntity'] !== ClassUtils::getClass($relation)) {
                continue;
            }
            if ($mapping['type'] !== ClassMetadata::ONE_TO_MANY) {
                continue;
            }
            $mappedBy = $mapping['mappedBy'];
            $setter = $this->getSetterName($mappedBy);
            $relation->$setter($value);
        }
    }

    /**
     * @param string $mappedBy
     * @return string
     */
    protected function getSetterName($mappedBy)
    {
        $parts = explode('_', $mappedBy);
        $setter = 'set';
        foreach ($parts as $part) {
            $setter .= ucfirst($part);
        }

        return $setter;
    }
}
