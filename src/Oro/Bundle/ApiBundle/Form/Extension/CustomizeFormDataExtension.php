<?php

namespace Oro\Bundle\ApiBundle\Form\Extension;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataHandler;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Initializes the form builder by adding all options required to handle
 * all types of form related events dispatched in "customize_form_data" action,
 * and registers handlers for all the events, except "pre_validate" and "post_validate".
 * The "pre_validate" and "post_validate" events are processed by FormValidationHandler,
 * because the deferred validation is used in API.
 * @see \Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension
 * @see \Oro\Bundle\ApiBundle\Form\FormValidationHandler
 */
class CustomizeFormDataExtension extends AbstractTypeExtension
{
    private ActionProcessorInterface $customizationProcessor;
    private CustomizeFormDataHandler $customizationHandler;

    public function __construct(
        ActionProcessorInterface $customizationProcessor,
        CustomizeFormDataHandler $customizationHandler
    ) {
        $this->customizationProcessor = $customizationProcessor;
        $this->customizationHandler = $customizationHandler;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (empty($options['data_class'])) {
            return;
        }

        if (\array_key_exists(CustomizeFormDataHandler::API_CONTEXT, $options)) {
            $builder->setAttribute(
                CustomizeFormDataHandler::API_CONTEXT,
                $options[CustomizeFormDataHandler::API_CONTEXT]
            );
        }

        $this->addEventListeners($builder);
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefined([CustomizeFormDataHandler::API_CONTEXT])
            ->setAllowedTypes(CustomizeFormDataHandler::API_CONTEXT, ['null', FormContext::class]);
    }

    /**
     * {@inheritDoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    private function addEventListeners(FormBuilderInterface $builder): void
    {
        // the same context object is used for all listeners to allow sharing the data between them
        $builder->setAttribute(CustomizeFormDataHandler::API_EVENT_CONTEXT, $this->createContext());

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $context = $this->handleFormEvent(CustomizeFormDataContext::EVENT_PRE_SUBMIT, $event);
                if (null !== $context) {
                    $event->setData($context->getData());
                }
            },
            -255 // this listener should be executed after all other listeners
        );
        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                $context = $this->handleFormEvent(CustomizeFormDataContext::EVENT_SUBMIT, $event);
                if (null !== $context) {
                    $event->setData($context->getData());
                }
            },
            -255 // this listener should be executed after all other listeners
        );
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $this->handleFormEvent(CustomizeFormDataContext::EVENT_POST_SUBMIT, $event);
            },
            255 // this listener should be executed before all other listeners, including the validation one
        );
    }

    private function createContext(): CustomizeFormDataContext
    {
        return $this->customizationProcessor->createContext();
    }

    private function handleFormEvent(string $eventName, FormEvent $event): ?CustomizeFormDataContext
    {
        return $this->customizationHandler->handleFormEvent($eventName, $event);
    }
}
