<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DateFieldChoiceType extends FieldChoiceType
{
    const NAME = 'oro_date_field_choice';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'include_fields' => [
                ['type' => 'datetime'],
            ],
        ]);

        parent::setDefaultOptions($resolver);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
