<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class DashboardType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('label', 'text', array('required' => true, 'label' => 'oro.dashboard.label'));
        $builder->add('owner', 'oro_user_select', array('required' => true, 'label' => 'oro.dashboard.owner'));
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'oro_dashboard_edit';
    }
}
