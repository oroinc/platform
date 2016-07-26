<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class WidgetDateType
 * @package Oro\Bundle\DashboardBundle\Form\Type
 */
class WidgetDateType extends AbstractType
{
    const NAME = 'oro_type_widget_date';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'useDate',
            'checkbox',
            [
                'label'      => false,
                'required'   => false
            ]
        );

        if ($options['use_date']) {
            $builder->add(
                'date',
                'oro_date',
                [
                    'required' => false,
                    'label' => false
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('use_date', true);
    }
}
