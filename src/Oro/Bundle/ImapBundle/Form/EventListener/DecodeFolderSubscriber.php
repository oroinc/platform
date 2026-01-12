<?php

namespace Oro\Bundle\ImapBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Handles decoding of folder data in form submission events.
 *
 * This event subscriber listens to form `PRE_SUBMIT` events and decodes JSON-encoded folder data
 * back into an array structure. It ensures that folder information submitted through forms is
 * properly deserialized before being processed by the form validation and data transformation layers.
 */
class DecodeFolderSubscriber implements EventSubscriberInterface
{
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SUBMIT   => ['decodeFolders', 255]
        ];
    }

    /**
     * Decode folders from json pack
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
