<?php

namespace Oro\Bundle\FormBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Converts array form data to a delimited string during form submission.
 *
 * This listener handles the PRE_SUBMIT event to transform array values into
 * delimited strings using a configurable delimiter. This is useful for form fields
 * that accept multiple values but need to be stored or processed as a single string.
 */
class FixArrayToStringListener implements EventSubscriberInterface
{
    private $delimiter;

    /**
     * @param string $delimiter
     */
    public function __construct($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    public function preSubmit(FormEvent $event)
    {
        $value = $event->getData();
        if (is_array($value)) {
            $event->setData(implode($this->delimiter, $value));
        }
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return array(FormEvents::PRE_SUBMIT => 'preSubmit');
    }
}
