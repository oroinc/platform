<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DashboardType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('label', 'text', array('required' => true, 'label' => 'oro.dashboard.label'));

        if ($options['create_new']) {
            $builder->add(
                'startDashboard',
                'oro_dashboard_select',
                array('required' => false, 'label' => 'oro.dashboard.start_dashboard')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'create_new' => false,
                'data_class' => 'Oro\\Bundle\\DashboardBundle\\Entity\\Dashboard'
            )
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
        return 'oro_dashboard';
    }
}
