<?php

namespace Oro\Bundle\ImapBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

class OriginFolderSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SUBMIT   => 'setOriginToFolders'
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function setOriginToFolders(FormEvent $event)
    {
        $data = $event->getData();
        if ($data !== null && $data instanceof UserEmailOrigin) {
            foreach ($data->getFolders() as $folder) {
                $folder->setOrigin($data);
            }
            $event->setData($data);
        }
    }
}
