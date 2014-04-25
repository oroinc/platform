<?php

namespace Oro\Bundle\ReportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class ReportChartChoiceType extends AbstractType
{
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
        return 'oro_report_chart_choice';
    }
}
