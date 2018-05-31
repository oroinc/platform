<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Oro\Bundle\DashboardBundle\Form\Type\DashboardSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DashboardType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('label', TextType::class, array('required' => true, 'label' => 'oro.dashboard.label'));

        if ($options['create_new']) {
            $builder->add(
                'startDashboard',
                DashboardSelectType::class,
                array('required' => false, 'label' => 'oro.dashboard.start_dashboard')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
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
