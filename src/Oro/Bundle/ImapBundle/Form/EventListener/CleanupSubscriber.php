<?php

namespace Oro\Bundle\ImapBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Performs additional fields cleanup on removal of
 * the origin data
 */
class CleanupSubscriber implements EventSubscriberInterface
{
    public const CLEANUP_FIELDS = [
        'checkFolder',
        'checkFolder',
        'folders',
    ];

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT   => 'cleanupEmptyFields',
        ];
    }

    /**
     * Performs unneeded fields cleanup
     * when IMAP Oauth-aware
     */
    public function cleanupEmptyFields(FormEvent $formEvent): void
    {
        $form = $formEvent->getForm();
        if (null === $form->getData() && $this->formEventDataMatch($formEvent->getData())) {
            foreach (self::CLEANUP_FIELDS as $field) {
                if ($form->has($field)) {
                    $form->remove($field);
                }
            }

            $formEvent->stopPropagation();
        }
    }

    /**
     * @param mixed $eventData
     * @return bool
     */
    protected function formEventDataMatch($eventData): bool
    {
        return (null === $eventData) || (is_array($eventData)
            && (count($eventData) === 1)
            && isset($eventData['accountType']));
    }
}
