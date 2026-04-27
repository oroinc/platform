<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for filter by entity using autocomplete (no full entity hydration).
 */
class EntityAutocompleteFilterType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'field_type'    => OroJquerySelect2HiddenType::class,
            'field_options' => [],
        ]);
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceFilterType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_type_entity_autocomplete_filter';
    }
}
