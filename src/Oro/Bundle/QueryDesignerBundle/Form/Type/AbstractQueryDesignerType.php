<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;

abstract class AbstractQueryDesignerType extends AbstractType
{
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
                'filter_column_choice_type'   => 'oro_entity_field_select'
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
        $form->add(
            $factory->createNamed(
                'grouping',
                'oro_query_designer_grouping',
                null,
                array(
                    'mapped'             => false,
                    'column_choice_type' => $form->getConfig()->getOption('grouping_column_choice_type'),
                    'entity'             => $entity ? $entity : null,
                    'auto_initialize'    => false
                )
            )
        );
        $form->add(
            $factory->createNamed(
                'column',
                'oro_query_designer_column',
                null,
                array(
                    'mapped'             => false,
                    'column_choice_type' => $form->getConfig()->getOption('column_column_choice_type'),
                    'entity'             => $entity ? $entity : null,
                    'auto_initialize'    => false
                )
            )
        );
        $form->add(
            $factory->createNamed(
                'filter',
                'oro_query_designer_filter',
                null,
                array(
                    'mapped'             => false,
                    'column_choice_type' => $form->getConfig()->getOption('filter_column_choice_type'),
                    'entity'             => $entity ? $entity : null,
                    'auto_initialize'    => false
                )
            )
        );
    }
}
