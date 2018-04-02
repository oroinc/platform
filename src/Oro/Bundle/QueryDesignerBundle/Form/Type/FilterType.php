<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityFieldSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FilterType extends AbstractType
{
    const NAME = 'oro_query_designer_filter';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('criterion', TextType::class, array('required' => true));

        $factory = $builder->getFormFactory();
        if ($options['column_choice_type']) {
            $builder->addEventListener(
                FormEvents::PRE_SET_DATA,
                function (FormEvent $event) use ($factory) {
                    $form = $event->getForm();

                    $form->add(
                        $factory->createNamed(
                            'columnName',
                            $form->getConfig()->getOption('column_choice_type'),
                            null,
                            [
                                'required'           => true,
                                'entity'             => $form->getConfig()->getOption('entity'),
                                'skip_load_entities' => true,
                                'skip_load_data'     => true,
                                'with_relations'     => true,
                                'auto_initialize'    => false
                            ]
                        )
                    );
                }
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
                'entity'             => null,
                'data_class'         => 'Oro\Bundle\QueryDesignerBundle\Model\Filter',
                'csrf_token_id'      => 'query_designer_filter',
                'column_choice_type' => EntityFieldSelectType::class
            )
        );
    }

    /**
     * {@inheritdoc}
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
        return self::NAME;
    }
}
