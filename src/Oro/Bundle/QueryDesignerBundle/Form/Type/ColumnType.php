<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class ColumnType extends AbstractType
{
    const NAME = 'oro_query_designer_column';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'oro_field_choice', array('required' => true))
            ->add('label', 'text', array('required' => true))
            ->add('func', 'oro_function_choice', array('required' => false))
            ->add('sorting', 'oro_sorting_choice', array('required' => false));

        /*
        $factory = $builder->getFormFactory();
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($factory) {
                $form = $event->getForm();

                $form->add(
                    $factory->createNamed(
                        'name',
                        $form->getConfig()->getOption('column_choice_type'),
                        null,
                        array(
                            'required'           => true,
                            'auto_initialize'    => false,
                            'entity'             => $form->getConfig()->getOption('entity'),
                            'skip_load_entities' => true,
                            'skip_load_data'     => true,
                            'with_relations'     => true,
                        )
                    )
                );
            }
        );
        */
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'entity'             => null,
                'data_class'         => 'Oro\Bundle\QueryDesignerBundle\Model\Column',
                'intention'          => 'query_designer_column',
                'column_choice_type' => 'oro_entity_field_select'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
