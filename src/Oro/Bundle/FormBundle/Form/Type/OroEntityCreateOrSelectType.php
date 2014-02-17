<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class OroEntityCreateOrSelectType extends AbstractType
{
    const NAME = 'oro_entity_create_or_select';

    const MODE_CREATE = 'create';
    const MODE_GRID   = 'grid';
    const MODE_VIEW   = 'view';

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // new entity
        $builder->add(
            'new_entity',
            $options['create_entity_form_type'],
            array_merge(
                $options['create_entity_form_options'],
                array(
                    'data_class' => $options['data_class'],
                    'mapped' => false
                )
            )
        );

        // existing entity
        $builder->add(
            'existing_entity',
            'oro_entity_identifier',
            array(
                'class' => $options['data_class'],
                'multiple' => false,
                'mapped' => false
            )
        );

        // rendering mode
        $builder->add(
            'mode',
            'hidden',
            array(
                'mapped' => false
            )
        );
    }

    /**
     * Important options:
     * - create_entity_form_type - form type used to render create entity form
     * - create_entity_form_options - options for create entity form
     * - grid_name - name of the grid used to select existing entity
     * - view_widgets - array with list of widgets used to render entity view // TODO: add widget parameters format
     * - mode - view rendering mode, by default guessed based on data:
     *      - self::MODE_CREATE - entity create form is rendered
     *      - self::MODE_GRID - grid with allowed entities is rendered
     *      - self::MODE_VIEW - entity view is rendered
     *
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array(
                'data_class',
                'create_entity_form_type',
                'grid_name',
                'view_widgets'
            )
        );

        $resolver->setDefaults(
            array(
                'create_entity_form_options' => array(),
                'mode' => self::MODE_CREATE,
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['grid_name']    = $options['grid_name'];
        $view->vars['view_widgets'] = $options['view_widgets'];
    }
}
