<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresource;

use Oro\Bundle\ApiBundle\Form\FormHelper;
use Oro\Bundle\ApiBundle\Form\Type\CollectionType;
use Oro\Bundle\ApiBundle\Form\Type\ObjectType;
use Oro\Bundle\ApiBundle\Processor\Subresource\ChangeSubresourceContext;
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
     * @param FormBuilderInterface     $formBuilder
     * @param ChangeSubresourceContext $context
     */
    protected function addFormFields(FormBuilderInterface $formBuilder, ChangeSubresourceContext $context): void
    {
        $entryDataClass = $context->getClassName();
        $entryFormOptions = $this->getEntryFormOptions($context);
        if (\array_key_exists('data_class', $entryFormOptions)) {
            $entryDataClass = $entryFormOptions['data_class'];
            unset($entryFormOptions['data_class']);
        }
        $formBuilder->add(
            $context->getAssociationName(),
            CollectionType::class,
            [
                'by_reference'     => !$this->enableAdderAndRemover,
                'entry_data_class' => $entryDataClass,
                'entry_type'       => ObjectType::class,
                'entry_options'    => $entryFormOptions
            ]
        );
    }
}
