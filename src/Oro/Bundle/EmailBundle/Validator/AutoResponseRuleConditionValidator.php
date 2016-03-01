<?php

namespace Oro\Bundle\EmailBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\EmailBundle\Entity\AutoResponseRuleCondition;

class AutoResponseRuleConditionValidator extends ConstraintValidator
{
    /**
     * @param AutoResponseRuleCondition|null $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value) {
            return;
        }

        if ($this->filterValueShouldBeEmpty($value->getFilterType())) {
            if ($value->getFilterValue()) {
                $this->context->addViolation($constraint->nonEmptyInputMessage);
            }
        } elseif (!$value->getFilterValue()) {
            $this->context->addViolation($constraint->emptyInputMessage);
        }
    }

    /**
     * @param bool $filterType
     */
    protected function filterValueShouldBeEmpty($filterType)
    {
        return in_array($filterType, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY]);
    }
}
