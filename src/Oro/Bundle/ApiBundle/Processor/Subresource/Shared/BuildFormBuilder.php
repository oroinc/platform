<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataHandler;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Builds the form builder based on the parent entity configuration
 * and sets it to the context.
 */
class BuildFormBuilder implements ProcessorInterface
{
    private FormHelper $formHelper;

    public function __construct(FormHelper $formHelper)
    {
        $this->formHelper = $formHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var ChangeRelationshipContext $context */

        if ($context->hasFormBuilder()) {
            // the form builder is already built
            return;
        }
        if ($context->hasForm()) {
            // the form is already built
            return;
        }

        if (!$context->hasParentEntity()) {
            // the entity is not defined
            throw new RuntimeException(
                'The parent entity object must be added to the context before creation of the form builder.'
            );
        }

        $context->setFormBuilder($this->getFormBuilder($context));
    }

    protected function getFormBuilder(ChangeRelationshipContext $context): FormBuilderInterface
    {
        $parentConfig = $context->getParentConfig();
        $associationName = $context->getAssociationName();

        $formEventSubscribers = null;
        if (!$parentConfig->getFormType()) {
            $formEventSubscribers = $parentConfig->getFormEventSubscribers();
        }
        $formBuilder = $this->formHelper->createFormBuilder(
            FormType::class,
            $context->getParentEntity(),
            $this->getFormOptions($context, $parentConfig),
            $formEventSubscribers
        );
        $this->formHelper->addFormField(
            $formBuilder,
            $associationName,
            $parentConfig->getField($associationName),
            $context->getParentMetadata()->getAssociation($associationName)
        );

        return $formBuilder;
    }

    protected function getFormOptions(ChangeRelationshipContext $context, EntityDefinitionConfig $parentConfig): array
    {
        $options = $parentConfig->getFormOptions();
        if (null === $options) {
            $options = [];
        }
        if (!\array_key_exists('data_class', $options)) {
            $options['data_class'] = $context->getParentClassName();
        }
        $options[CustomizeFormDataHandler::API_CONTEXT] = $context;

        return $options;
    }
}
