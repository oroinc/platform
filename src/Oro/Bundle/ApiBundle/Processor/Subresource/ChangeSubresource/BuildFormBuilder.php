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
        $config = $context->getConfig();
        if (null === $config) {
            return null;
        }
        $metadata = $context->getMetadata();
        if (null === $metadata) {
            return null;
        }

        $formBuilder = $this->createFormBuilder($context);
        $formBuilder->add(
            $context->getAssociationName(),
            ObjectType::class,
            [
                'data_class' => $context->getClassName(),
                'metadata'   => $metadata,
                'config'     => $config
            ]
        );

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
            []
        );
        if ($this->enableFullValidation) {
            $formBuilder->addEventSubscriber(new EnableFullValidationListener());
        }

        return $formBuilder;
    }
}
