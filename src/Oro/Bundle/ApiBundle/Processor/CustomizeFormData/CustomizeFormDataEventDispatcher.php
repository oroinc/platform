<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Oro\Bundle\ApiBundle\Form\FormUtil;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

/**
 * Represents the dispatcher for the "customize_form_data" action events.
 */
class CustomizeFormDataEventDispatcher
{
    private CustomizeFormDataHandler $customizationHandler;

    public function __construct(CustomizeFormDataHandler $customizationHandler)
    {
        $this->customizationHandler = $customizationHandler;
    }

    /**
     * Dispatches the given "customize_form_data" action event for the given form.
     *
     * @throws \InvalidArgumentException if the given form is not root form or it is not submitted yet
     */
    public function dispatch(string $eventName, FormInterface $form): void
    {
        FormUtil::assertSubmittedRootForm($form);

        $this->dispatchEventForChildren($eventName, $form);
        if ($this->hasApiEventContext($form)) {
            $this->dispatchEvent($eventName, $form);
        }
    }

    private function dispatchEventForChildren(string $eventName, FormInterface $form): void
    {
        /** @var FormInterface $child */
        foreach ($form as $child) {
            if ($child->count() > 0) {
                $this->dispatchEventForChildren($eventName, $child);
            }
            if ($this->hasApiEventContext($child)) {
                $this->dispatchEvent($eventName, $child);
            }
        }
    }

    private function dispatchEvent(string $eventName, FormInterface $form): void
    {
        $this->customizationHandler->handleFormEvent($eventName, new FormEvent($form, $form->getViewData()));
    }

    private function hasApiEventContext(FormInterface $form): bool
    {
        return $form->getConfig()->hasAttribute(CustomizeFormDataHandler::API_EVENT_CONTEXT);
    }
}
