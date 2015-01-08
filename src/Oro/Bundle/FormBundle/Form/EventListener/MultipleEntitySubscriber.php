<?php

namespace Oro\Bundle\FormBundle\Form\EventListener;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

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

        /** @var Collection $collection */
        $collection = $form->getData();
        foreach ($added as $relation) {
            $collection->add($relation);
        }

        foreach ($removed as $relation) {
            $collection->removeElement($relation);
        }
    }
}
