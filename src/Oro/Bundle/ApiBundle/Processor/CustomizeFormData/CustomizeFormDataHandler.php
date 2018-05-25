<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

/**
 * Provides functionality to handle all types of form related events dispatched in "customize_form_data" action.
 */
class CustomizeFormDataHandler
{
    public const API_CONTEXT       = 'api_context';
    public const API_EVENT_CONTEXT = 'api_event_context';

    /** @var ActionProcessorInterface */
    private $customizationProcessor;

    /**
     * @param ActionProcessorInterface $customizationProcessor
     */
    public function __construct(ActionProcessorInterface $customizationProcessor)
    {
        $this->customizationProcessor = $customizationProcessor;
    }

    /**
     * @param string    $eventName
     * @param FormEvent $event
     *
     * @return CustomizeFormDataContext|null
     */
    public function handleFormEvent(string $eventName, FormEvent $event): ?CustomizeFormDataContext
    {
        $context = $this->getInitializedContext($event->getForm());
        if (null !== $context) {
            $context->setEvent($eventName);
            $context->setData($event->getData());
            $this->customizationProcessor->process($context);
        }

        return $context;
    }

    /**
     * @param FormInterface $form
     *
     * @return CustomizeFormDataContext|null
     */
    private function getInitializedContext(FormInterface $form): ?CustomizeFormDataContext
    {
        /** @var CustomizeFormDataContext $context */
        $context = $form->getConfig()->getAttribute(self::API_EVENT_CONTEXT);
        if ($context->has(CustomizeFormDataContext::CLASS_NAME)) {
            // already initialized
            return $context;
        }

        $rootFormConfig = $form->getConfig();
        if (!$rootFormConfig->hasAttribute(self::API_CONTEXT)) {
            // by some reasons the root form does not have the context of API action
            return null;
        }

        /** @var FormContext $formContext */
        $formContext = $rootFormConfig->getAttribute(self::API_CONTEXT);
        $context->setVersion($formContext->getVersion());
        $context->getRequestType()->set($formContext->getRequestType());
        $context->setConfig($formContext->getConfig());
        $context->setClassName($form->getConfig()->getDataClass());
        $context->setForm($form);
        if (null !== $form->getParent()) {
            $context->setRootClassName($rootFormConfig->getDataClass());
            $context->setPropertyPath($this->getPropertyPath($form));
        }

        return $context;
    }

    /**
     * @param FormInterface $form
     *
     * @return string
     */
    private function getPropertyPath(FormInterface $form): string
    {
        $path = [];
        while (null !== $form->getParent()->getParent()) {
            if (!$form->getData() instanceof Collection) {
                if ($form->getParent()->getData() instanceof Collection) {
                    $path[] = $form->getParent()->getName();
                } else {
                    $path[] = $form->getName();
                }
            }
            $form = $form->getParent();
        }

        return \implode('.', \array_reverse($path));
    }
}
