<?php

namespace Oro\Bundle\TagBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TaxonomyType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'name',
            'text',
            array (
                'label'    => 'oro.taxonomy.name.label',
                'required' => true,
            )
        )
            ->add(
                'backgroundColor',
                'oro_simple_color_picker',
                [
                    'required'           => false,
                    'label'              => 'oro.taxonomy.background_color.label',
//                    'color_schema'       => 'oro_tag.taxonomy_colors',
                    'color_schema'       => 'oro_calendar.calendar_colors',
                    'empty_value'        => 'oro.taxonomy.calendarevent.no_color',
                    'allow_empty_color'  => true,
                    'allow_custom_color' => true,
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array (
                'data_class' => 'Oro\Bundle\TagBundle\Entity\Taxonomy',
                'intention'  => 'taxonomy',
                'grid_name' => 'taxonomy-grid',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_tag_taxonomy';
    }
}
