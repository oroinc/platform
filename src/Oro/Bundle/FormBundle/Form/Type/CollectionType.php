<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\FormBundle\Form\EventListener\CollectionTypeSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType as SymfonyCollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectionType extends AbstractType
{
    const NAME = 'oro_collection';

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
                'handle_primary'       => $options['handle_primary'],
                'show_form_when_empty' => $options['show_form_when_empty'],
                'prototype_name'       => $options['prototype_name'],
                'add_label'            => $options['add_label'],
                'allow_add_after'      => $options['allow_add_after'],
                'row_count_add'        => $options['row_count_add'],
                'row_count_initial'    => $options['row_count_initial'],
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'allow_add'            => true,
                'allow_add_after'      => false,
                'allow_delete'         => true,
                'by_reference'         => false,
                'prototype'            => true,
                'prototype_name'       => '__name__',
                'handle_primary'       => true,
                'show_form_when_empty' => true,
                'add_label'            => '',
                'row_count_add'        => 1,
                'row_count_initial'    => 1,
            ]
        );
        $resolver->setRequired(['entry_type']);
        $resolver->setNormalizer('show_form_when_empty', function (Options $options, $value) {
            return !$options['allow_add'] ? false : $value;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return SymfonyCollectionType::class;
    }

    /**
     * {@inheritdoc}
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
        return static::NAME;
    }
}
