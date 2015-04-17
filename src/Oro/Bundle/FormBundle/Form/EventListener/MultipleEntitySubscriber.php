<?php

namespace Oro\Bundle\FormBundle\Form\EventListener;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MultipleEntitySubscriber implements EventSubscriberInterface
{
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

        /** @var Collection $collection */
        $collection = $form->getData();

        $mapping = [];
        if ($collection instanceof PersistentCollection) {
            $mapping = $collection->getMapping();
        }

        foreach ($added as $relation) {
            if ($mapping && $mapping['type'] === ClassMetadata::ONE_TO_MANY) {
                $mappedBy = $mapping['mappedBy'];
                $setter = $this->getSetterName($mappedBy);
                $relation->$setter($parentData);
            }
            $collection->add($relation);
        }

        foreach ($removed as $relation) {
            if ($mapping && $mapping['type'] === ClassMetadata::ONE_TO_MANY) {
                $mappedBy = $mapping['mappedBy'];
                $setter = $this->getSetterName($mappedBy);
                $relation->$setter(null);
            }
            $collection->removeElement($relation);
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
