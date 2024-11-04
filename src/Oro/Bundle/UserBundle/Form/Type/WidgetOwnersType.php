<?php

namespace Oro\Bundle\UserBundle\Form\Type;

use Oro\Bundle\OrganizationBundle\Form\Type\WidgetBusinessUnitSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WidgetOwnersType extends AbstractType
{
    const NAME = 'oro_type_widget_owners';

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'businessUnits',
            WidgetBusinessUnitSelectType::class,
            [
                'label'    => 'oro.sales.dashboard.forecast_of_opportunities.business_unit',
                'required' => false,
            ]
        );
        $builder->add(
            'roles',
            WidgetRoleSelectType::class,
            [
                'label'    => 'oro.sales.dashboard.forecast_of_opportunities.role',
                'required' => false
            ]
        );
        $builder->add(
            'users',
            WidgetUserSelectType::class,
            [
                'label'    => 'oro.sales.dashboard.forecast_of_opportunities.owner',
                'required' => false
            ]
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
}
