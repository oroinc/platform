<?php

namespace Oro\Bundle\ApiBundle\Form\Extension;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Processor\FormContext;

class CustomizeFormDataExtension extends AbstractTypeExtension
{
    const API_CONTEXT       = 'api_context';
    const API_EVENT_CONTEXT = 'api_event_context';

    /** @var ActionProcessorInterface */
    protected $customizationProcessor;

    /**
     * @param ActionProcessorInterface $customizationProcessor
     */
    public function __construct(ActionProcessorInterface $customizationProcessor)
    {
        $this->customizationProcessor = $customizationProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (empty($options['data_class'])) {
            return;
        }

        if (array_key_exists(self::API_CONTEXT, $options)) {
            $builder->setAttribute(self::API_CONTEXT, $options[self::API_CONTEXT]);
        }

        $this->addEventListeners($builder);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined([self::API_CONTEXT])
            ->setAllowedTypes(self::API_CONTEXT, ['null', FormContext::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return FormType::class;
    }

    /**
     * @param FormBuilderInterface $builder
     */
    protected function addEventListeners(FormBuilderInterface $builder)
    {
        // the same context object is used for all listeners to allow sharing the data between them
        $builder->setAttribute(self::API_EVENT_CONTEXT, $this->customizationProcessor->createContext());

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
        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                $this->handleFormEvent(CustomizeFormDataContext::EVENT_FINISH_SUBMIT, $event);
            },
            -255 // this listener should be executed after all other listeners, including the validation one
        );
    }

    /**
     * @param string    $eventName
     * @param FormEvent $event
     *
     * @return CustomizeFormDataContext|null
     */
    protected function handleFormEvent($eventName, FormEvent $event)
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
    protected function getInitializedContext(FormInterface $form)
    {
        /** @var CustomizeFormDataContext $context */
        $context = $form->getConfig()->getAttribute(self::API_EVENT_CONTEXT);
        if ($context->has(CustomizeFormDataContext::CLASS_NAME)) {
            // already initialized
            return $context;
        }

        $rootFormConfig = $form->getRoot()->getConfig();
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
    protected function getPropertyPath(FormInterface $form)
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

        return implode('.', array_reverse($path));
    }
}
