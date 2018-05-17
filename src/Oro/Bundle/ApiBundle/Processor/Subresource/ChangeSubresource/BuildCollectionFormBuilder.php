<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource;

use Oro\Bundle\ApiBundle\Form\EventListener\EnableFullValidationListener;
use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Form\Type\CollectionType;
use Oro\Bundle\ApiBundle\Form\Type\ObjectType;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Builds the form builder for a collection based change sub-resource request and sets it to the context.
 */
class BuildCollectionFormBuilder extends BuildFormBuilder
{
    /** @var bool */
    protected $enableAdderAndRemover;

    /**
     * @param FormHelper $formHelper
     * @param bool       $enableFullValidation
     * @param bool       $enableAdderAndRemover
     */
    public function __construct(
        FormHelper $formHelper,
        bool $enableFullValidation = false,
        bool $enableAdderAndRemover = false
    ) {
        parent::__construct($formHelper, $enableFullValidation);
        $this->enableAdderAndRemover = $enableAdderAndRemover;
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
            CollectionType::class,
            [
                'by_reference'     => !$this->enableAdderAndRemover,
                'entry_data_class' => $context->getClassName(),
                'entry_type'       => ObjectType::class,
                'entry_options'    => [
                    'metadata' => $metadata,
                    'config'   => $config
                ]
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
