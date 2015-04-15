<?php

namespace Oro\Bundle\DataGridBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\DataGridBundle\Entity\GridView;

class GridViewType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', 'text', [
                'property_path' => 'name',
            ])
            ->add('type', 'choice', [
                'choices' => GridView::getTypes(),
            ])
            ->add('grid_name', 'text', [
                'property_path' => 'gridName',
            ])
            ->add('filters', null, [
                'property_path' => 'filtersData',
            ])
            ->add('sorters', 'collection', [
                'property_path' => 'sorters_data',
                'error_bubbling' => false,
                'allow_add' => true,
                'allow_delete' => true,
                'type' => 'choice',
                'options' => [
                    'choices' => [
                        1 => 1,
                        -1 => -1
                    ],
                ],
            ]);
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
