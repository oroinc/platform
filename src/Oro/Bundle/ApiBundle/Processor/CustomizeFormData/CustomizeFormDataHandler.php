<?php

namespace Oro\Bundle\ApiBundle\Processor\CustomizeFormData;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

/**
 * Provides functionality to handle all types of form related events dispatched in "customize_form_data" action.
 */
class CustomizeFormDataHandler
{
    public const API_CONTEXT = 'api_context';
    public const API_EVENT_CONTEXT = 'api_event_context';

    private ActionProcessorInterface $customizationProcessor;

    public function __construct(ActionProcessorInterface $customizationProcessor)
    {
        $this->customizationProcessor = $customizationProcessor;
    }

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

    private function getInitializedContext(FormInterface $form): ?CustomizeFormDataContext
    {
        /** @var CustomizeFormDataContext $context */
        $context = $form->getConfig()->getAttribute(self::API_EVENT_CONTEXT);
        if ($context->isInitialized()) {
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
        $context->setSharedData($formContext->getSharedData());
        $context->setClassName($form->getConfig()->getDataClass());
        $context->setParentAction($this->getParentAction($formContext));
        $context->setForm($form);
        $config = $formContext->getConfig();
        if (null === $form->getParent()) {
            $context->setConfig($config);
        } else {
            $context->setRootClassName($rootFormConfig->getDataClass());
            $propertyPath = $this->getPropertyPath($form);
            $context->setPropertyPath($propertyPath);
            $context->setRootConfig($config);
            if (null !== $config) {
                $context->setConfig($this->getAssociationConfig($config, $propertyPath));
            }
        }
        $includedEntities = $formContext->getIncludedEntities();
        if (null !== $includedEntities) {
            $context->setIncludedEntities($includedEntities);
        }
        $context->setAdditionalEntityCollection($formContext->getAdditionalEntityCollection());
        $context->setEntityMapper($formContext->getEntityMapper());

        return $context;
    }

    private function getParentAction(FormContext $formContext): string
    {
        $action = $formContext->getAction();
        switch ($action) {
            case ApiAction::CREATE:
                if ($formContext->isExisting()) {
                    $action = ApiAction::UPDATE;
                }
                break;
            case ApiAction::UPDATE:
                if (!$formContext->isExisting()) {
                    $action = ApiAction::CREATE;
                }
                break;
        }

        return $action;
    }

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

        return implode('.', array_reverse($path));
    }

    private function getAssociationConfig(
        EntityDefinitionConfig $config,
        string $propertyPath
    ): ?EntityDefinitionConfig {
        $currentConfig = $config;
        $path = ConfigUtil::explodePropertyPath($propertyPath);
        foreach ($path as $fieldName) {
            $fieldConfig = $currentConfig->getField($fieldName);
            $currentConfig = null;
            if (null !== $fieldConfig) {
                $currentConfig = $fieldConfig->getTargetEntity();
            }
            if (null === $currentConfig) {
                break;
            }
        }

        return $currentConfig;
    }
}
