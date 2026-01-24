<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting date fields in query designer.
 *
 * This specialized field choice type restricts the available field options to only
 * date and datetime fields. It extends {@see FieldChoiceType} and filters the field selection
 * to support date-based grouping and filtering operations in query designer queries.
 */
class DateFieldChoiceType extends FieldChoiceType
{
    const NAME = 'oro_date_field_choice';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'include_fields' => [
                ['type' => 'datetime'],
            ],
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
