<?php

namespace Oro\Bundle\ReportBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ReportColumnDublicateConstraint extends Constraint
{
    /**
     * @var string
     */
    public $columnIsDublicate = 'oro.report.dublicate.columns';
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
        return 'oro_report.report_column_dublicate_validator';
    }
}
