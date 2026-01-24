<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityFieldSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for configuring query designer filters.
 *
 * This form type provides a user interface for defining filter conditions in a query designer query.
 * It allows users to select a column to filter on and specify the filter criterion. The form
 * dynamically adds a column choice field based on the configured column choice type, enabling
 * context-aware field selection for the filter condition.
 */
class FilterType extends AbstractType
{
    const NAME = 'oro_query_designer_filter';

    #[\Override]
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

    #[\Override]
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

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
