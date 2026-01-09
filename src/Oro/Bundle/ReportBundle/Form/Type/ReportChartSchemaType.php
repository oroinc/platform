<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for configuring chart data schema fields in reports.
 *
 * Manages the dynamic creation of form fields based on chart data schema configuration.
 * Handles field type selection (text or checkbox), applies type filters to exclude
 * incompatible properties, and integrates with the query designer manager to provide
 * intelligent field filtering. Includes client-side validation and data type filtering
 * through custom attributes.
 */
class ReportChartSchemaType extends AbstractType
{
    public const VIEW_MODULE_NAME = 'ororeport/js/app/views/report-chart-data-schema-view';

    /**
     * @var Manager
     */
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['data_schema'] as $schemaOptions) {
            $excludedProperties = [];

            if (isset($schemaOptions['type_filter']) && $schemaOptions['type_filter']) {
                $excludedProperties = array_merge(
                    $excludedProperties,
                    $this->manager->getExcludedProperties($schemaOptions['type_filter'])
                );
            }

            $fieldOptions = [
                'label'    => $schemaOptions['label'],
                'required' => $schemaOptions['required'],
                'attr'     => [
                    'data-type-filter'               => json_encode($excludedProperties),
                    'data-validation-optional-group' => true,
                    'data-validation'                => json_encode(['NotBlank' => []])
                ]
            ];

            $builder->add(
                $schemaOptions['name'],
                $schemaOptions['default_type'] !== 'boolean' ? TextType::class : CheckboxType::class,
                $fieldOptions
            );
        }
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['attr']['data-page-component-view'] = self::VIEW_MODULE_NAME;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(['data_schema']);
        $resolver->setAllowedTypes('data_schema', 'array');
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_report_chart_data_schema';
    }
}
