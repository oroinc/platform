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
        $eventContext = $this->getApiEventContext($form);
        if (null !== $eventContext) {
            $this->customizationHandler->handleFormEvent($eventName, new FormEvent($form, $eventContext->getData()));
        }
    }

    private function dispatchEventForChildren(string $eventName, FormInterface $form): void
    {
        /** @var FormInterface $child */
        foreach ($form as $child) {
            if ($child->count() > 0) {
                $this->dispatchEventForChildren($eventName, $child);
            }
            $childEventContext = $this->getApiEventContext($child);
            if (null !== $childEventContext) {
                $this->customizationHandler->handleFormEvent(
                    $eventName,
                    new FormEvent($child, $childEventContext->getData())
                );
            }
        }
    }

    private function getApiEventContext(FormInterface $form): ?CustomizeFormDataContext
    {
        return $form->getConfig()->getAttribute(CustomizeFormDataHandler::API_EVENT_CONTEXT);
    }
}
