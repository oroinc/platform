<?php

namespace Oro\Bundle\ConfigBundle\Form\EventSubscriber;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Form\EventSubscriber\FileSubscriber;

class ConfigFileSubscriber extends FileSubscriber
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::SUBMIT  => 'submit'
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function submit(FormEvent $event)
    {
        /** @var File $entity */
        $entity = $event->getData();

        $this->triggerEntityUpdate($event->getForm(), $entity);
    }
}
