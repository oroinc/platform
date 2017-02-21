<?php

namespace Oro\Bundle\ReportBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ReportDateGroupingConstraint extends Constraint
{
    /**
     * @var string
     */
    public $message = 'oro.report.date_grouping.group_by_mandatory';

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }

    /**
     * {@inheritdoc}
     */
    public function validatedBy()
    {
        return 'oro_report.report_date_grouping_validator';
    }
}
