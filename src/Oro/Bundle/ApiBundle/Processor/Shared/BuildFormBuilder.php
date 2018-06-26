<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataHandler;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Builds the form builder based on the entity metadata and configuration
 * and sets it to the context.
 */
class BuildFormBuilder implements ProcessorInterface
{
    /** @var FormHelper */
    protected $formHelper;

    /** @var bool */
    protected $enableFullValidation;

    /**
     * @param FormHelper $formHelper
     * @param bool       $enableFullValidation
     */
    public function __construct(FormHelper $formHelper, bool $enableFullValidation = false)
    {
        $this->formHelper = $formHelper;
        $this->enableFullValidation = $enableFullValidation;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var FormContext $context */

        if ($context->hasFormBuilder()) {
            // the form builder is already built
            return;
        }
        if ($context->hasForm()) {
            // the form is already built
            return;
        }

        if (!$context->hasResult()) {
            // the entity is not defined
            throw new RuntimeException(
                'The entity object must be added to the context before creation of the form builder.'
            );
        }

        $formBuilder = $this->getFormBuilder($context);
        if (null !== $formBuilder) {
            $context->setFormBuilder($formBuilder);
        }
    }

    /**
     * @param FormContext $context
     *
     * @return FormBuilderInterface|null
     */
    protected function getFormBuilder(FormContext $context)
    {
        $config = $context->getConfig();
        if (null === $config) {
            return null;
        }

        $formType = $config->getFormType() ?: FormType::class;

        $formBuilder = $this->formHelper->createFormBuilder(
            $formType,
            $context->getResult(),
            $this->getFormOptions($context, $config),
            $config->getFormEventSubscribers()
        );

        if (FormType::class === $formType) {
            $metadata = $context->getMetadata();
            if (null !== $metadata) {
                $this->formHelper->addFormFields($formBuilder, $metadata, $config);
            }
        }

        return $formBuilder;
    }

    /**
     * @param FormContext            $context
     * @param EntityDefinitionConfig $config
     *
     * @return array
     */
    protected function getFormOptions(FormContext $context, EntityDefinitionConfig $config)
    {
        $options = $config->getFormOptions();
        if (null === $options) {
            $options = [];
        }
        if (!\array_key_exists('data_class', $options)) {
            $options['data_class'] = $this->getFormDataClass($context, $config);
        }
        $options[CustomizeFormDataHandler::API_CONTEXT] = $context;
        $options[ValidationExtension::ENABLE_FULL_VALIDATION] = $this->enableFullValidation;

        return $options;
    }

    /**
     * @param FormContext            $context
     * @param EntityDefinitionConfig $config
     *
     * @return string
     */
    protected function getFormDataClass(FormContext $context, EntityDefinitionConfig $config)
    {
        $dataClass = $context->getClassName();
        $entity = $context->getResult();
        if (\is_object($entity)) {
            $parentResourceClass = $config->getParentResourceClass();
            if ($parentResourceClass) {
                $entityClass = ClassUtils::getClass($entity);
                if ($entityClass !== $dataClass && $entityClass === $parentResourceClass) {
                    $dataClass = $parentResourceClass;
                }
            }
        }

        return $dataClass;
    }
}
