<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Form\FormHelper;
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
    /** @var FormHelper */
    protected $formHelper;

    /**
     * @param FormHelper $formHelper
     */
    public function __construct(FormHelper $formHelper)
    {
        $this->formHelper = $formHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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

    /**
     * @param ChangeRelationshipContext $context
     *
     * @return FormBuilderInterface
     */
    protected function getFormBuilder(ChangeRelationshipContext $context)
    {
        $parentConfig = $context->getParentConfig();
        $associationName = $context->getAssociationName();

        $formOptions = $parentConfig->getFormOptions();
        if (null === $formOptions) {
            $formOptions = [];
        }
        if (!\array_key_exists('data_class', $formOptions)) {
            $formOptions['data_class'] = $context->getParentClassName();
        }
        $formEventSubscribers = null;
        if (!$parentConfig->getFormType()) {
            $formEventSubscribers = $parentConfig->getFormEventSubscribers();
        }
        $formBuilder = $this->formHelper->createFormBuilder(
            FormType::class,
            $context->getParentEntity(),
            $formOptions,
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
}
