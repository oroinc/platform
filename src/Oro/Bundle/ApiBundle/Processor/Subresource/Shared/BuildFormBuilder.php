<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeRelationshipContext;

/**
 * Builds the form builder based on the parent entity configuration
 * and sets it to the Context.
 */
class BuildFormBuilder implements ProcessorInterface
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
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

        $context->setFormBuilder($this->getFormBuilder($context));
    }

    /**
     * @param ChangeRelationshipContext $context
     *
     * @return FormBuilderInterface
     */
    protected function getFormBuilder(ChangeRelationshipContext $context)
    {
        $formBuilder = $this->formFactory->createNamedBuilder(
            null,
            'form',
            $context->getParentEntity(),
            array_merge(
                FormUtil::getFormDefaultOptions(),
                ['data_class' => $context->getParentClassName()]
            )
        );

        $associationName = $context->getAssociationName();
        $associationConfig = $context->getParentConfig()->getField($associationName);
        $association = $context->getParentMetadata()->getAssociation($associationName);

        $formBuilder->add(
            $associationName,
            $associationConfig->getFormType(),
            FormUtil::getFormFieldOptions($association, $associationConfig)
        );

        return $formBuilder;
    }
}
