<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Form\Extension\ValidationExtension;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Form\Type\ObjectType;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataHandler;
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
    private FormHelper $formHelper;
    private bool $enableFullValidation;

    public function __construct(FormHelper $formHelper, bool $enableFullValidation = false)
    {
        $this->formHelper = $formHelper;
        $this->enableFullValidation = $enableFullValidation;
    }

    #[\Override]
    public function process(ContextInterface $context): void
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

    protected function getFormBuilder(ChangeSubresourceContext $context): ?FormBuilderInterface
    {
        if (null === $context->getRequestConfig()) {
            return null;
        }
        if (null === $context->getRequestMetadata()) {
            return null;
        }

        $formBuilder = $this->createFormBuilder($context);
        $this->addFormFields($formBuilder, $context);

        return $formBuilder;
    }

    protected function createFormBuilder(ChangeSubresourceContext $context): FormBuilderInterface
    {
        return $this->formHelper->createFormBuilder(
            FormType::class,
            $context->getResult(),
            $this->getFormOptions($context)
        );
    }

    protected function addFormFields(FormBuilderInterface $formBuilder, ChangeSubresourceContext $context): void
    {
        $entryFormOptions = $this->getEntryFormOptions($context);
        if (!\array_key_exists('data_class', $entryFormOptions)) {
            $entryFormOptions['data_class'] = $context->getRequestClassName();
        }
        $formBuilder->add(
            $context->getAssociationName(),
            ObjectType::class,
            $entryFormOptions
        );
    }

    protected function getFormOptions(ChangeSubresourceContext $context): array
    {
        $options = [];
        $optionsFromConfig = $context->getRequestConfig()->getFormOptions();
        if (!empty($optionsFromConfig) && isset($optionsFromConfig['validation_groups'])) {
            $options['validation_groups'] = $optionsFromConfig['validation_groups'];
        }
        $options[ValidationExtension::ENABLE_FULL_VALIDATION] = $this->enableFullValidation || !$context->isExisting();

        return array_merge($options, $context->getFormOptions() ?? []);
    }

    protected function getEntryFormOptions(ChangeSubresourceContext $context): array
    {
        $config = $context->getRequestConfig();

        $entryFormOptions = [
            'metadata' => $context->getRequestMetadata(),
            'config'   => $config
        ];
        $options = $config->getFormOptions();
        if (!empty($options)) {
            unset($options['validation_groups']);
            $entryFormOptions = array_replace($entryFormOptions, $options);
        }
        $entryFormOptions[CustomizeFormDataHandler::API_CONTEXT] = $context;

        return $entryFormOptions;
    }
}
