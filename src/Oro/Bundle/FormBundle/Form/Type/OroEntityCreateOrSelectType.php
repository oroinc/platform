<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\FormBundle\Form\DataTransformer\EntityCreateOrSelectTransformer;

class OroEntityCreateOrSelectType extends AbstractType
{
    const NAME = 'oro_entity_create_or_select';

    const MODE_CREATE = 'create';
    const MODE_GRID   = 'grid';
    const MODE_VIEW   = 'view';

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

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
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) use ($options) {
                $data = $event->getData();
                $mode = !empty($data['mode']) ? $data['mode'] : $options['mode'];

                if ($mode != OroEntityCreateOrSelectType::MODE_CREATE) {
                    $this->disableNewEntityValidation($event->getForm(), $options);
                }
            }
        );

        $builder->addViewTransformer(
            new EntityCreateOrSelectTransformer($this->doctrineHelper, $options['class'], $options['mode'])
        );

        // new entity
        $builder->add(
            'new_entity',
            $options['create_entity_form_type'],
            $this->getNewEntityFormOptions($options)
        );

        // existing entity
        $builder->add(
            'existing_entity',
            'oro_entity_identifier',
            array(
                'required' => $options['required'],
                'class' => $options['class'],
                'multiple' => false,
            )
        );

        // rendering mode
        $builder->add(
            'mode',
            'text', // TODO use hidden
            array()
        );
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getNewEntityFormOptions(array $options)
    {
        return array_merge(
            $options['create_entity_form_options'],
            array(
                'required' => $options['required'],
                'data_class' => $options['class'],
            )
        );
    }

    /**
     * @param FormInterface $form
     * @param array $options
     */
    protected function disableNewEntityValidation(FormInterface $form, array $options)
    {
        // disable all validation for new entity field
        $form->remove('new_entity');
        $form->add(
            'new_entity',
            $options['create_entity_form_type'],
            array_merge(
                $this->getNewEntityFormOptions($options),
                array('validation_groups' => false)
            )
        );
    }

    /**
     * Important options:
     * - class - FQCN used for this form type
     * - create_entity_form_type - form type used to render create entity form
     * - create_entity_form_options - options for create entity form
     * - grid_name - name of the grid used to select existing entity
     * - view_widgets - array with list of widgets used to render entity view (YAML formatted example)
     *      - {
     *          'route_name': '',
     *          'route_parameters':
     *              <route_parameter_name>: string|PropertyPath
     *              ...
     *          'grid_row_to_route':
     *              <route_parameter_name>: <grid_row_field_name>,
     *          'widget_alias' => form_id+route_name
     *      }
     *
     *
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
                'class',
                'create_entity_form_type',
                'grid_name',
                'view_widgets'
            )
        );

        $resolver->setDefaults(
            array(
                'create_entity_form_options' => array(),
                'mode' => self::MODE_CREATE,
                'existing_entity_grid_id' => 'id'
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
        $view->vars['existing_entity_grid_id'] = $options['existing_entity_grid_id'];
    }
}
