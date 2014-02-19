<?php

namespace Oro\Bundle\FormBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Exception\InvalidConfigurationException;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FormBundle\Form\DataTransformer\EntityCreateOrSelectTransformer;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyPath;

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
        // disable validation for new entity in case of existing entity
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

        // transform data from array to entity and vice versa
        $builder->addViewTransformer(
            new EntityCreateOrSelectTransformer($this->doctrineHelper, $options['class'], $options['mode'])
        );

        // new entity field
        $builder->add(
            'new_entity',
            $options['create_entity_form_type'],
            $this->getNewEntityFormOptions($options)
        );

        // existing entity field
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
        $builder->add('mode', 'hidden');
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
     * - existing_entity_grid_id - grid row field name used as entity identifier
     * - view_widgets - array with list of widgets used to render entity view (YAML formatted example)
     *      - {
     *          'route_name': '',
     *          'route_parameters': (optional)
     *              <route_parameter_name>: string|PropertyPath
     *              ...
     *          'grid_row_to_route':
     *              <route_parameter_name>: <grid_row_field_name>,
     *          'widget_alias' => 'my_widget_alias' (optional)
     *      }
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
                'existing_entity_grid_id' => 'id',
                'mode' => self::MODE_CREATE,
            )
        );

        $resolver->setNormalizers(
            array(
                'view_widgets' => function (Options $options, array $viewWidgets) {
                    foreach ($viewWidgets as $key => $widgetData) {
                        if (empty($widgetData['route_name'])) {
                            throw new InvalidConfigurationException(
                                'Widget route name is not defined'
                            );
                        }

                        if (empty($widgetData['grid_row_to_route'])) {
                            throw new InvalidConfigurationException(
                                'Mapping between grid row and route parameters is not defined'
                            );
                        }

                        if (!array_key_exists('route_parameters', $widgetData)) {
                            $widgetData['route_parameters'] = array();
                        }

                        if (empty($widgetData['widget_alias'])) {
                            $widgetData['widget_alias'] = self::NAME . '_' . $widgetData['route_name'];
                        }

                        $viewWidgets[$key] = $widgetData;
                    }

                    return $viewWidgets;
                }
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $viewWidgets = $options['view_widgets'];
        $entity = $form->getData();
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($viewWidgets as $key => $widgetData) {
            foreach ($widgetData['route_parameters'] as $name => $value) {
                if ($value instanceof PropertyPath) {
                    try {
                        $value = $propertyAccessor->getValue($entity, $value);
                    } catch (\Exception $e) {
                        $value = null;
                    }
                    $widgetData['route_parameters'][$name] = $value;
                }
            }
            $viewWidgets[$key] = $widgetData;
        }

        $view->vars['view_widgets'] = $viewWidgets;
        $view->vars['grid_name'] = $options['grid_name'];
        $view->vars['existing_entity_grid_id'] = $options['existing_entity_grid_id'];
    }
}
