<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting column sorting direction in query designer.
 *
 * This form type provides a choice field for specifying the sorting direction (ascending or descending)
 * for columns in a query designer query. It offers a user-friendly interface with translatable labels
 * for the available sorting options.
 */
class SortingChoiceType extends AbstractType
{
    const NAME = 'oro_sorting_choice';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices'     => [
                    'oro.query_designer.form.sorting_asc' => 'ASC',
                    'oro.query_designer.form.sorting_desc' => 'DESC',
                ],
                'placeholder' => 'oro.query_designer.form.choose_sorting',
                'empty_data'  => ''
            )
        );
    }

    #[\Override]
    public function getParent(): ?string
    {
        return ChoiceType::class;
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
}
