<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroSimpleColorPickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaxonomyType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            TextType::class,
            array(
                'label'    => 'oro.taxonomy.name.label',
                'required' => true,
            )
        )
            ->add(
                'backgroundColor',
                OroSimpleColorPickerType::class,
                [
                    'required'           => false,
                    'label'              => 'oro.taxonomy.background_color.label',
                    'color_schema'       => 'oro_tag.taxonomy_colors',
                    'empty_value'        => 'oro.taxonomy.calendarevent.no_color',
                    'allow_empty_color'  => true,
                    'allow_custom_color' => true,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\TagBundle\Entity\Taxonomy',
                'csrf_token_id' => 'taxonomy',
                'grid_name' => 'taxonomy-grid',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_tag_taxonomy';
    }
}
