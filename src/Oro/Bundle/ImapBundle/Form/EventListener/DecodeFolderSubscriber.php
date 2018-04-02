<?php

namespace Oro\Bundle\ImapBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DecodeFolderSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT   => ['decodeFolders', 255]
        ];
    }

    /**
     * Decode folders from json pack
     *
     * @param FormEvent $event
     */
    public function decodeFolders(FormEvent $event)
    {
        $data = $event->getData();
        if (!$data || !is_array($data) || !array_key_exists('folders', $data)) {
            return;
        }

        if (!is_string($data['folders'])) {
            return;
        }

        $data['folders'] = json_decode($data['folders'], true);
        $event->setData($data);
    }
}
