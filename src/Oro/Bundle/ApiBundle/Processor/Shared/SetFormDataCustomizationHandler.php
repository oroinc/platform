<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * Builds the form using the form builder from the Context.
 */
class SetFormDataCustomizationHandler implements ProcessorInterface
{
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
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        if ($context->hasForm()) {
            // the form is already built
            return;
        }
        $formBuilder = $context->getFormBuilder();
        if (null === $formBuilder) {
            // the data handler cannot be set because the form builder does not exist
            return;
        }

        $formBuilder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) use ($context) {
                $this->handleFormData($event->getForm(), $context);
            },
            -255
        );
    }

    /**
     * @param FormInterface $form
     * @param FormContext   $context
     */
    protected function handleFormData(FormInterface $form, FormContext $context)
    {
        $rootEntityClass = $form->getConfig()->getDataClass();
        if (empty($rootEntityClass)) {
            return;
        }

        $handlers = [];
        $this->addChildHandlers($handlers, $form);
        $handlers = array_reverse($handlers);
        foreach ($handlers as $handler) {
            $this->handleEntity(
                $context,
                $rootEntityClass,
                $handler['property_path'],
                $handler['data_class'],
                $handler['form']
            );
        }
        $this->handleRootEntity($context, $rootEntityClass, $form);
    }

    /**
     * @param array         $handlers
     * @param FormInterface $form
     * @param null          $propertyPath
     * @param bool          $isCollection
     */
    protected function addChildHandlers(
        array &$handlers,
        FormInterface $form,
        $propertyPath = null,
        $isCollection = false
    ) {
        /** @var FormInterface $childForm */
        foreach ($form as $fieldName => $childForm) {
            $entityClass = $childForm->getConfig()->getDataClass();
            if (!empty($entityClass)) {
                $childPropertyPath = !$isCollection
                    ? $this->buildFieldPath($fieldName, $propertyPath)
                    : $propertyPath;
                $handlers[] = [
                    'form'          => $childForm,
                    'data_class'    => $entityClass,
                    'property_path' => $childPropertyPath
                ];
                $this->addChildHandlers($handlers, $childForm, $childPropertyPath);
            } elseif ($childForm->getViewData() instanceof Collection) {
                $this->addChildHandlers(
                    $handlers,
                    $childForm,
                    $this->buildFieldPath($fieldName, $propertyPath),
                    true
                );
            }
        }
    }

    /**
     * @param string      $fieldName
     * @param string|null $parentFieldPath
     *
     * @return string
     */
    protected function buildFieldPath($fieldName, $parentFieldPath = null)
    {
        return null !== $parentFieldPath
            ? $parentFieldPath . ConfigUtil::PATH_DELIMITER . $fieldName
            : $fieldName;
    }

    /**
     * @param FormContext $context
     *
     * @return CustomizeFormDataContext
     */
    protected function createCustomizationContext(FormContext $context)
    {
        /** @var CustomizeFormDataContext $customizationContext */
        $customizationContext = $this->customizationProcessor->createContext();
        $customizationContext->setVersion($context->getVersion());
        $customizationContext->getRequestType()->set($context->getRequestType());

        return $customizationContext;
    }

    /**
     * @param FormContext   $context
     * @param string        $entityClass
     * @param FormInterface $form
     */
    protected function handleRootEntity(
        FormContext $context,
        $entityClass,
        FormInterface $form
    ) {
        $customizationContext = $this->createCustomizationContext($context);
        $customizationContext->setClassName($entityClass);
        $customizationContext->setConfig($context->getConfig());
        $customizationContext->setForm($form);
        $customizationContext->setResult($form->getViewData());
        $this->customizationProcessor->process($customizationContext);
    }

    /**
     * @param FormContext   $context
     * @param string        $rootEntityClass
     * @param string        $propertyPath
     * @param string        $entityClass
     * @param FormInterface $form
     */
    protected function handleEntity(
        FormContext $context,
        $rootEntityClass,
        $propertyPath,
        $entityClass,
        FormInterface $form
    ) {
        $customizationContext = $this->createCustomizationContext($context);
        $customizationContext->setRootClassName($rootEntityClass);
        $customizationContext->setPropertyPath($propertyPath);
        $customizationContext->setClassName($entityClass);
        $customizationContext->setConfig($context->getConfig());
        $customizationContext->setForm($form);
        $customizationContext->setResult($form->getViewData());
        $this->customizationProcessor->process($customizationContext);
    }
}
