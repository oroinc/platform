<?php

namespace Oro\Bundle\ReportBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ReportColumnDuplicateConstraint extends Constraint
{
    /**
     * @var string
     */
    public $columnIsDuplicate = 'oro.report.duplicate.columns';

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
        return 'oro_report.report_column_duplicate_validator';
    }
}
