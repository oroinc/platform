<?php

namespace Oro\Bundle\DataGridBundle\Form\Type;

use Oro\Bundle\DataGridBundle\Entity\GridView;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GridViewType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text')
            ->add('type', 'choice', [
                'choices' => GridView::getTypes(),
            ])
            ->add('grid_name', 'text', [
                'property_path' => 'gridName',
            ])
            ->add('filters_data', 'collection', [
                'property_path' => 'filtersData',
                'error_bubbling' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'type' => 'oro_type_filter',
                'options' => [
                    'operator_type' => 'text',
                ]
            ])
            ->add('sorters_data', 'collection', [
                'property_path' => 'sorters_data',
                'error_bubbling' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'type' => 'oro_datagrid_sorting',
            ])
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\DataGridBundle\Entity\GridView',
            'csrf_protection' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_datagrid_grid_view';
    }
}
