<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WidgetOwnersType extends AbstractType
{
    const NAME = 'oro_type_widget_owners';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'businessUnits',
            'oro_type_widget_business_unit_select',
            [
                'label'    => 'oro.sales.dashboard.forecast_of_opportunities.business_unit',
                'required' => false,
            ]
        );
        $builder->add(
            'roles',
            'oro_type_widget_role_select',
            [
                'label'    => 'oro.sales.dashboard.forecast_of_opportunities.role',
                'required' => false
            ]
        );
        $builder->add(
            'users',
            'oro_type_widget_user_select',
            [
                'label'    => 'oro.sales.dashboard.forecast_of_opportunities.owner',
                'required' => false
            ]
        );
    }

    /**
     * {@inheritdoc}
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
}
