<?php

namespace Oro\Bundle\DataGridBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SortingType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('field', 'text')
            ->add('direction', 'oro_sorting_choice')
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_datagrid_sorting';
    }
}
