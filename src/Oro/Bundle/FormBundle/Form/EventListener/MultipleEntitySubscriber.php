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

        /** @var PersistentCollection|Collection $collection */
        $collection = $form->getData();

        $collectionMappedBy = null;
        if ($collection instanceof PersistentCollection) {
            $collectionMapping  = $collection->getMapping();
            $collectionMappedBy = $collectionMapping['mappedBy'];
        }

        foreach ($added as $relation) {
            if ($collectionMappedBy) {
                $this->processRelation($parentMetadata, $relation, $collectionMappedBy, $parentData);
            }
            $collection->add($relation);
        }

        foreach ($removed as $relation) {
            if ($collectionMappedBy) {
                $this->processRelation($parentMetadata, $relation, $collectionMappedBy, null);
            }
            $collection->removeElement($relation);
        }
    }

    /**
     * @param ClassMetadata $metadata
     * @param object        $relation
     * @param string        $mappedBy
     * @param mixed         $value
     */
    protected function processRelation($metadata, $relation, $mappedBy, $value)
    {
        $relationClassName = ClassUtils::getClass($relation);
        foreach ($metadata->getAssociationMappings() as $mapping) {
            if ($this->isApplicableRelation($mapping, $relationClassName, $mappedBy)) {
                $setter = $this->getSetterName($mappedBy);
                $relation->$setter($value);
                break;
            }
        }
    }

    /**
     * @param array  $mapping
     * @param string $relationClassName
     * @param string $mappedBy
     *
     * @return bool
     */
    protected function isApplicableRelation($mapping, $relationClassName, $mappedBy)
    {
        if (!is_array($mapping) || !isset($mapping['targetEntity'], $mapping['type'], $mapping['mappedBy'])) {
            return false;
        }
        if ($mapping['targetEntity'] !== $relationClassName) {
            return false;
        }
        if ($mapping['type'] !== ClassMetadata::ONE_TO_MANY) {
            return false;
        }
        if ($mapping['mappedBy'] !== $mappedBy) {
            return false;
        }

        return true;
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
