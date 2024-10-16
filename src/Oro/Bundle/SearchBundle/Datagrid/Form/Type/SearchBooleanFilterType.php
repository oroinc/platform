<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type which can be used as a boolean filter for datagrids with search datasource.
 */
class SearchBooleanFilterType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setNormalizer('field_options', function (Options $options, $value) {
            if (!$value) {
                $value = [];
            }
            $value['multiple'] = true;

            return $value;
        });
    }

    #[\Override]
    public function getParent(): ?string
    {
        return BooleanFilterType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_search_type_boolean_filter';
    }
}
