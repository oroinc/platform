<?php

namespace Oro\Bundle\FilterBundle\Form\Type\Filter;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SkipEmptyPeriodsFilterType extends AbstractChoiceType
{
    const NAME = 'oro_type_skip_empty_periods_filter';
    const TYPE_YES = 'Yes';
    const TYPE_NO = 'No';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return ChoiceFilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'field_type'       => 'choice',
                'field_options'    => ['choices' => $this->getGroupChoices()],
                'default_value'    => self::TYPE_YES,
                'null_value'       => null,
                'class'            => null,
                'populate_default' => true,
            )
        );
    }

    protected function getGroupChoices()
    {
        return [
            self::TYPE_NO,
        ];
    }
}
