<?php

namespace Oro\Bundle\DataGridBundle\Form\Type;

use Oro\Bundle\DataGridBundle\Entity\GridView;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GridViewType extends AbstractType
{
    /**
     * Example of usage:
     *     Sorters options choices:
     *     '-1': 'ASC',
     *     '1': 'DESC'
     *
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('label', TextType::class, [
                'property_path' => 'name',
            ])
            ->add('is_default', CheckboxType::class, [
                'required' => false,
                'mapped'   => false,
            ])
            ->add('type', ChoiceType::class, [
                'choices' => GridView::getTypes(),
            ])
            ->add('grid_name', TextType::class, [
                'property_path' => 'gridName',
            ])
            ->add(
                'appearanceType',
                EntityType::class,
                [
                    'class' => 'OroDataGridBundle:AppearanceType',
                    'property_path' => 'appearanceType',
                    'required' => false,
                ]
            )
            ->add('appearanceData', TextType::class, [
                'property_path' => 'appearanceData',
                'empty_data'    => [],
                'required' => false,
            ])
            ->add('filters', null, [
                'property_path' => 'filtersData',
                'empty_data'    => [],
            ])
            ->add('sorters', CollectionType::class, [
                'property_path'  => 'sorters_data',
                'error_bubbling' => false,
                'allow_add'      => true,
                'allow_delete'   => true,
                'entry_type'     => ChoiceType::class,
                'entry_options'  => [
                    'choices' => [
                        1  => 1,
                        -1 => -1
                    ],
                ],
            ])
            ->add('columns', null, [
                'property_path' => 'columns_data',
                'empty_data'    => []
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Oro\Bundle\DataGridBundle\Entity\AbstractGridView',
            'csrf_protection' => false,
        ]);
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
        return 'oro_datagrid_grid_view';
    }
}
