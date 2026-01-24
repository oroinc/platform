<?php

namespace Oro\Bundle\CommentBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Handles form events for comment forms.
 *
 * This subscriber listens to the `PRE_SET_DATA` event and removes the `owner` field
 * from the comment form, ensuring that comment ownership is managed by the system
 * rather than being set by the form user.
 */
class CommentSubscriber implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();

        if ($form->has('owner')) {
            $form->remove('owner');
        }
    }
}
