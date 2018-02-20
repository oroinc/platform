<?php

namespace Oro\Bundle\ReportBundle\Validator;

use Oro\Bundle\QueryDesignerBundle\Form\Type\DateGroupingType;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Validator\Constraints\ReportDateGroupingConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

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
        $dateGroupingDef = $definition[DateGroupingType::DATE_GROUPING_NAME];

        if (!isset($definition['grouping_columns']) || empty($definition['grouping_columns'])) {
            $this->context->addViolation($constraint->groupByMandatoryMessage);
        }

        if (isset($dateGroupingDef[DateGroupingType::USE_DATE_GROUPING_FILTER])
            && !isset($dateGroupingDef[DateGroupingType::FIELD_NAME_ID])
        ) {
            $this->context->addViolation($constraint->dateFieldMandatoryMessage);
        }
    }
}
