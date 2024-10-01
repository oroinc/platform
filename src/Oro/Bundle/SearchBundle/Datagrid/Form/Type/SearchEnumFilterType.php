<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Form\Type;

use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchEnumFilterType extends AbstractType
{
    const NAME = 'oro_search_type_enum_filter';

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
