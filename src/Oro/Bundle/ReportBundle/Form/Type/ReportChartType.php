<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ReportChartType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('data_schema', 'oro_report_chart_data_schema_collection');
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_chart';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_report_chart';
    }
}
