<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for filtering search results by enumeration fields.
 *
 * This form type extends the standard {@see EnumFilterType} to provide filtering
 * capabilities for enumeration fields in search-based datagrids. It normalizes
 * operator choices from the field options to ensure proper filtering behavior.
 */
class SearchEnumFilterType extends AbstractType
{
    public const NAME = 'oro_search_type_enum_filter';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setNormalizer(
            'operator_choices',
            function (Options $options) {
                $fieldOptions = $options->offsetGet('field_options');

                return $fieldOptions['choices'];
            }
        );
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }

    #[\Override]
    public function getParent(): ?string
    {
        return EnumFilterType::class;
    }
}
