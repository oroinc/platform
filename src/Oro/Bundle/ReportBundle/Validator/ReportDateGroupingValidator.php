<?php

namespace Oro\Bundle\ReportBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Validator\Constraints\ReportDateGroupingConstraint;
use Oro\Bundle\QueryDesignerBundle\Form\Type\DateGroupingType;

class ReportDateGroupingValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        /** @var ReportDateGroupingConstraint $constraint */
        if (!$value instanceof Report) {
            return;
        }

        $definition = json_decode($value->getDefinition(), true);

        if (!isset($definition[DateGroupingType::DATE_GROUPING_NAME])) {
            return;
        }

        if (!isset($definition['grouping_columns']) || empty($definition['grouping_columns'])) {
            $this->context->addViolation($constraint->message);
        }
    }
}
