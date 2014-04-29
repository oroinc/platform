<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ReportChartSchemaType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['data_schema'] as $schemaOptions) {
            $builder->add(
                $schemaOptions['name'],
                'text',
                [
                    'label'    => $schemaOptions['label'],
                    'required' => true
                ]
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
