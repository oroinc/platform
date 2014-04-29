<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;

class ReportChartSchemaType extends AbstractType
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['data_schema'] as $schemaOptions) {
            $excludedProperties = ['children'];

            if (isset($schemaOptions['filter'])) {
                $excludedProperties = array_merge(
                    $excludedProperties,
                    $this->manager->getExcludedProperties($schemaOptions['filter'])
                );
            }

            $fieldOptions = [
                'label'    => $schemaOptions['label'],
                'required' => $schemaOptions['required'],
                'attr'     => [
                    'data-filter' => json_encode($excludedProperties)
                ]
            ];

            $builder->add(
                $schemaOptions['name'],
                'text',
                $fieldOptions
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['data_schema']);
        $resolver->setAllowedTypes(['data_schema' => 'array']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_report_chart_data_schema';
    }
}
