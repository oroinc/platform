<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;

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
