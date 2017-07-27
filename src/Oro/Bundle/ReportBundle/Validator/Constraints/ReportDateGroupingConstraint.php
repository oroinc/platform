<?php

namespace Oro\Bundle\ReportBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ReportDateGroupingConstraint extends Constraint
{
    /**
     * @var string
     */
    public $groupByMandatoryMessage = 'oro.report.date_grouping.group_by_mandatory';
    public $dateFieldMandatoryMessage = 'oro.report.date_grouping.date_field.mandatory';

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
