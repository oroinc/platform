<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Model\DateGrouping;

abstract class AbstractQueryDesignerType extends AbstractType
{
    const DATE_GROUPING_NAME = 'dateGrouping';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('definition', 'hidden', array('required' => false));

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

        $builder->addEventListener(
            FormEvents::POST_SET_DATA,
            function (FormEvent $event) {
                if (!$this->isDateGroupingInvolved($event)) {
                    return;
                }
                $dateGroupingModel = $event->getForm()->get(static::DATE_GROUPING_NAME)->getData();
                if (!$dateGroupingModel instanceof DateGrouping) {
                    $dateGroupingModel = new DateGrouping();
                }
                $definition = json_decode($event->getData()->getDefinition(), true);
                if (!is_array($definition) || !array_key_exists(static::DATE_GROUPING_NAME, $definition)) {
                    return;
                }

                $dateGroupingArray = $definition[static::DATE_GROUPING_NAME];
                if (array_key_exists(DateGroupingType::FIELD_NAME_ID, $dateGroupingArray)) {
                    $dateGroupingModel->setFieldName($dateGroupingArray[DateGroupingType::FIELD_NAME_ID]);
                }

                if (array_key_exists(DateGroupingType::USE_SKIP_EMPTY_PERIODS_FILTER_ID, $dateGroupingArray)) {
                    $dateGroupingModel->setUseSkipEmptyPeriodsFilter(
                        $dateGroupingArray[DateGroupingType::USE_SKIP_EMPTY_PERIODS_FILTER_ID]
                    );
                }

                $event->getForm()->get(static::DATE_GROUPING_NAME)->setData($dateGroupingModel);
            }
        );

        $builder->addEventListener(
            FormEvents::SUBMIT,
            function (FormEvent $event) {
                if (!$this->isDateGroupingInvolved($event)) {
                    return;
                }

                $dateGroupingModel = $event->getForm()->get(static::DATE_GROUPING_NAME)->getData();
                $definition = json_decode($event->getData()->getDefinition(), true);
                if (!is_array($definition)) {
                    $definition = [];
                }
                if (!array_key_exists(static::DATE_GROUPING_NAME, $definition)) {
                    $definition[static::DATE_GROUPING_NAME] = [];
                }
                $definition[static::DATE_GROUPING_NAME][DateGroupingType::FIELD_NAME_ID] =
                    $dateGroupingModel->getFieldName();
                $definition[static::DATE_GROUPING_NAME][DateGroupingType::USE_SKIP_EMPTY_PERIODS_FILTER_ID] =
                    $dateGroupingModel->getUseSkipEmptyPeriodsFilter();
                // add default group by entity id if not provided
                if (!array_key_exists('grouping_columns', $definition) || count($definition['grouping_columns']) === 0) {
                    $definition['grouping_columns'][] = ['name' => 'id'];
                }
                $event->getData()->setDefinition(json_encode($definition));
            }
        );
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
                'grouping_column_choice_type' => 'hidden',
                'column_column_choice_type'   => 'hidden',
                'filter_column_choice_type'   => 'oro_entity_field_select',
                'date_grouping_choice_type'   => 'oro_entity_field_select'
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
                    'oro_query_designer_grouping',
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
                    'oro_query_designer_column',
                    null,
                    array(
                        'mapped'             => false,
                        'column_choice_type' => $columnChoiceType,
                        'entity'             => $entity,
                        'auto_initialize'    => false
                    )
                )
            );
        }

        $filterColumnChoiceType = $config->getOption('filter_column_choice_type');
        if ($filterColumnChoiceType) {
            $form->add(
                $factory->createNamed(
                    'filter',
                    'oro_query_designer_filter',
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
                    static::DATE_GROUPING_NAME,
                    'oro_query_designer_date_grouping',
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

    /**
     * @param FormEvent $event
     * @return bool
     */
    protected function isDateGroupingInvolved(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        if (!$data instanceof AbstractQueryDesigner || !$form->has(static::DATE_GROUPING_NAME)) {
            return false;
        }

        return true;
    }
}
