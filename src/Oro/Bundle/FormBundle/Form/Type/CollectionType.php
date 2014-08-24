<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\EventListener\CollectionTypeSubscriber;

class CollectionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(
            new CollectionTypeSubscriber()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace(
            $view->vars,
            [
                'show_form_when_empty' => $options['show_form_when_empty'] && $options['can_add_and_delete'],
                'can_add_and_delete'   => $options['can_add_and_delete']
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'allow_add'                => true,
                'allow_delete'             => true,
                'by_reference'             => false,
                'prototype'                => true,
                'prototype_name'           => '__name__',
                'extra_fields_message'     => 'This form should not contain extra fields: "{{ extra_fields }}"',
                'ignore_primary_behaviour' => false,
                'show_form_when_empty'     => true,
                'can_add_and_delete'       => true
            ]
        );
        $resolver->setRequired(['type']);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'collection';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_collection';
    }
}
