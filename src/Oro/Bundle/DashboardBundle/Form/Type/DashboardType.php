<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;

class DashboardType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('label', 'text', array('required' => true, 'label' => 'oro.dashboard.label'));
        /**
         * @var Dashboard $dataObject
         */
        $dataObject = $builder->getData();
        if (!$dataObject->getId()) {
            $builder->add(
                'startFrom',
                'oro_select_dashboard',
                array('required' => false, 'label' => 'oro.dashboard.start_from')
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array('data_class' => 'Oro\Bundle\DashboardBundle\Entity\Dashboard'));
    }

    /**
     * Returns the name of this type.
     *
     * @return string The name of this type
     */
    public function getName()
    {
        return 'oro_dashboard';
    }
}
