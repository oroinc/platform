<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Form\EventListener\EnableFullValidationListener;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Form\Type\ObjectType;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Builds the form builder for a change sub-resource request and sets it to the context.
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
        /** @var ChangeSubresourceContext $context */

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
     * @param ChangeSubresourceContext $context
     *
     * @return FormBuilderInterface|null
     */
    protected function getFormBuilder(ChangeSubresourceContext $context): ?FormBuilderInterface
    {
        if (null === $context->getConfig()) {
            return null;
        }
        if (null === $context->getMetadata()) {
            return null;
        }

        $formBuilder = $this->createFormBuilder($context);
        $this->addFormFields($formBuilder, $context);

        return $formBuilder;
    }

    /**
     * @param ChangeSubresourceContext $context
     *
     * @return FormBuilderInterface
     */
    protected function createFormBuilder(ChangeSubresourceContext $context): FormBuilderInterface
    {
        $formBuilder = $this->formHelper->createFormBuilder(
            FormType::class,
            $context->getResult(),
            $this->getFormOptions($context)
        );
        if ($this->enableFullValidation) {
            $formBuilder->addEventSubscriber(new EnableFullValidationListener());
        }

        return $formBuilder;
    }

    /**
     * @param FormBuilderInterface     $formBuilder
     * @param ChangeSubresourceContext $context
     */
    protected function addFormFields(FormBuilderInterface $formBuilder, ChangeSubresourceContext $context): void
    {
        $entryFormOptions = $this->getEntryFormOptions($context);
        if (!\array_key_exists('data_class', $entryFormOptions)) {
            $entryFormOptions['data_class'] = $context->getClassName();
        }
        $formBuilder->add(
            $context->getAssociationName(),
            ObjectType::class,
            $entryFormOptions
        );
    }

    /**
     * @param ChangeSubresourceContext $context
     *
     * @return array
     */
    protected function getFormOptions(ChangeSubresourceContext $context): array
    {
        $formOptions = [];
        $options = $context->getConfig()->getFormOptions();
        if (!empty($options)) {
            unset($options['entry_options']);
            $formOptions = \array_replace($formOptions, $options);
        }

        return $formOptions;
    }

    /**
     * @param ChangeSubresourceContext $context
     *
     * @return array
     */
    protected function getEntryFormOptions(ChangeSubresourceContext $context): array
    {
        $config = $context->getConfig();

        $entryFormOptions = [
            'metadata' => $context->getMetadata(),
            'config'   => $config
        ];
        $options = $config->getFormOptions();
        if (!empty($options) && \array_key_exists('entry_options', $options)) {
            $entryOptions = $options['entry_options'];
            if (!empty($entryOptions)) {
                $entryFormOptions = \array_replace($entryFormOptions, $entryOptions);
            }
        }

        return $entryFormOptions;
    }
}
