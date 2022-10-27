<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Oro\Bundle\QueryDesignerBundle\Form\Type\DateGroupingType;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates whether a query definition created by the query designer
 * has valid configuration of the grouping by date section.
 */
class DateGroupingValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof DateGrouping) {
            throw new UnexpectedTypeException($constraint, DateGrouping::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof AbstractQueryDesigner) {
            throw new UnexpectedTypeException($value, AbstractQueryDesigner::class);
        }

        $definition = QueryDefinitionUtil::safeDecodeDefinition($value->getDefinition());
        if (!isset($definition[DateGroupingType::DATE_GROUPING_NAME])) {
            return;
        }

        if (empty($definition['grouping_columns'])) {
            $this->context->addViolation($constraint->groupByMandatoryMessage);
        }

        $dateGrouping = $definition[DateGroupingType::DATE_GROUPING_NAME];
        if (isset($dateGrouping[DateGroupingType::USE_DATE_GROUPING_FILTER])
            && !isset($dateGrouping[DateGroupingType::FIELD_NAME_ID])
        ) {
            $this->context->addViolation($constraint->dateFieldMandatoryMessage);
        }
    }
}
