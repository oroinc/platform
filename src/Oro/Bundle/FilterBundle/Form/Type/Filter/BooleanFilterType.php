<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The form type which can be used as a boolean filter.
 */
class BooleanFilterType extends AbstractType
{
    public const TYPE_YES = 1;
    public const TYPE_NO = 2;

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('field_options', [
            'choices' => [
                'oro.filter.form.label_type_yes' => self::TYPE_YES,
                'oro.filter.form.label_type_no' => self::TYPE_NO
            ]
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getParent(): ?string
    {
        return ChoiceFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_type_boolean_filter';
    }
}
