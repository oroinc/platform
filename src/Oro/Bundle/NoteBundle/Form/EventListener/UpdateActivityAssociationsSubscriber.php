<?php

namespace Oro\Bundle\NoteBundle\Form\EventListener;

use Oro\Bundle\NoteBundle\Entity\Note;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class UpdateActivityAssociationsSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::SUBMIT => 'updateActivityAssociation',
        );
    }

    /**
     * @param FormEvent $event
     */
    public function updateActivityAssociation(FormEvent $event)
    {
        /** @see \Oro\Bundle\ActivityBundle\Form\Extension\ContextsExtension::buildForm */
        if ($event->getForm()->has('contexts')) {
            $contexts = $event->getForm()->get('contexts');
            $contextsData = $contexts->getData();
            /** @var Note $note */
            $note = $event->getData();
            $oldActivityTargets = $note->getActivityTargetEntities();
            foreach ($oldActivityTargets as $target) {
                $note->removeActivityTarget($target);
            }
            foreach ($contextsData as $newActivityTarget) {
                $note->addActivityTarget($newActivityTarget);
            }
        }
    }
}
