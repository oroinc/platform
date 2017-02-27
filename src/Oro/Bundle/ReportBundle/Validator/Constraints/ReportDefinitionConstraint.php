<?php

namespace Oro\Bundle\ReportBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class ReportDefinitionConstraint extends Constraint
{
    /**
     * @var string
     */
    public $columnIsMandatory = 'oro.report.definition.columns.mandatory';

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
        return 'oro_report.report_definition_validator';
    }
}
