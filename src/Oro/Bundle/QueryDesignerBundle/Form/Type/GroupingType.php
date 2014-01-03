<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class GroupingType extends AbstractType
{
    const NAME = 'oro_query_designer_grouping';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $factory = $builder->getFormFactory();
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($factory) {
                $form = $event->getForm();

                $form->add(
                    $factory->createNamed(
                        'columnNames',
                        $form->getConfig()->getOption('column_choice_type'),
                        null,
                        array(
                            'required'           => false,
                            'entity'             => $form->getConfig()->getOption('entity'),
                            'skip_load_entities' => true,
                            'with_relations'     => true,
                            'deep_level'         => 1,
                            'auto_initialize'    => false,
                            'multiple'           => true
                        )
                    )
                );
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'entity'             => null,
                'data_class'         => 'Oro\Bundle\QueryDesignerBundle\Model\Grouping',
                'intention'          => 'query_designer_grouping',
                'column_choice_type' => 'oro_entity_field_choice'
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
