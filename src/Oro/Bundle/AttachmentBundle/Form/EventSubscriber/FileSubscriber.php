<?php

namespace Oro\Bundle\AttachmentBundle\Form\EventSubscriber;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;

class FileSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::POST_SUBMIT => 'postSubmit'
        ];
    }

    /**
     * Add checkbox to delete attach file and delete owner select
     *
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $entity = $event->getData();
        $form = $event->getForm();
        if (is_object($entity) && $entity->getId() && $entity->getFilename() !== null) {
            $form->add(
                'emptyFile',
                'checkbox',
                [
                    'label' => 'oro.attachment.delete_file.label',
                    'required'  => false,
                ]
            );
        }
        $form->remove('owner');
    }

    /**
     * Trigger attachment entity update
     *
     * @param FormEvent $event
     */
    public function postSubmit(FormEvent $event)
    {
        /** @var Attachment $entity */
        $entity = $event->getData();
        $form = $event->getForm();
        if (($form->has('emptyFile') && $form->get('emptyFile')->getData())
            || (is_object($entity) && $entity->getFile() !== null)
        ) {
            // trigger update in entity
            $entity->setUpdatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        }
    }
}
