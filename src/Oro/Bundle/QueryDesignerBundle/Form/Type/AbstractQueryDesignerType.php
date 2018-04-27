<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Oro\Bundle\EntityBundle\Form\Type\EntityFieldSelectType;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractQueryDesignerType extends AbstractType
{
    const DATE_GROUPING_FORM_NAME = 'dateGrouping';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('definition', HiddenType::class, array('required' => false));

        $factory = $builder->getFormFactory();
        $that    = $this;
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($that, $factory) {
                $form = $event->getForm();
                /** @var AbstractQueryDesigner $data */
                $data = $event->getData();
                if ($data) {
                    $entity = $data->getEntity();
                } else {
                    $entity = null;
                }
                $that->addFields($form, $factory, $entity);
            }
        );

        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($that, $factory) {
                $form = $event->getForm();
                /** @var AbstractQueryDesigner $data */
                $data = $event->getData();
                if ($data) {
                    $entity = $data['entity'];
                } else {
                    $entity = null;
                }
                $that->addFields($form, $factory, $entity);
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['query_type']);
    }

    /**
     * Gets the default options for this type.
     *
     * @return array
     */
    public function getDefaultOptions()
    {
        return
            array(
                'grouping_column_choice_type'        => HiddenType::class,
                'column_column_choice_type'          => HiddenType::class,
                'filter_column_choice_type'          => EntityFieldSelectType::class,
                'date_grouping_choice_type'          => EntityFieldSelectType::class,
                'column_column_field_choice_options' => [],
            );
    }

    /**
     * Adds column and filters sub forms
     *
     * @param FormInterface        $form
     * @param FormFactoryInterface $factory
     * @param string|null          $entity
     */
    protected function addFields($form, $factory, $entity = null)
    {
        $config = $form->getConfig();

        $groupingColumnChoiceType = $config->getOption('grouping_column_choice_type');
        if ($groupingColumnChoiceType) {
            $form->add(
                $factory->createNamed(
                    'grouping',
                    GroupingType::class,
                    null,
                    array(
                        'mapped'             => false,
                        'column_choice_type' => $groupingColumnChoiceType,
                        'entity'             => $entity,
                        'auto_initialize'    => false
                    )
                )
            );
        }

        $columnChoiceType = $config->getOption('column_column_choice_type');
        if ($columnChoiceType) {
            $form->add(
                $factory->createNamed(
                    'column',
                    ColumnType::class,
                    null,
                    array(
                        'mapped'               => false,
                        'column_choice_type'   => $columnChoiceType,
                        'entity'               => $entity,
                        'auto_initialize'      => false,
                        'field_choice_options' => $config->getOption('column_column_field_choice_options'),
                        'query_type'           => $config->getOption('query_type'),
                    )
                )
            );
        }

        $filterColumnChoiceType = $config->getOption('filter_column_choice_type');
        if ($filterColumnChoiceType) {
            $form->add(
                $factory->createNamed(
                    'filter',
                    FilterType::class,
                    null,
                    array(
                        'mapped'             => false,
                        'column_choice_type' => $filterColumnChoiceType,
                        'entity'             => $entity,
                        'auto_initialize'    => false
                    )
                )
            );
        }

        $dateGroupingChoiceType = $config->getOption('date_grouping_choice_type');
        if ($dateGroupingChoiceType) {
            $form->add(
                $factory->createNamed(
                    AbstractQueryDesignerType::DATE_GROUPING_FORM_NAME,
                    DateGroupingType::class,
                    null,
                    [
                        'mapped' => false,
                        'column_choice_type' => $dateGroupingChoiceType,
                        'entity' => $entity,
                        'auto_initialize' => false
                    ]
                )
            );
        }
    }
}
