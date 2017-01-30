<?php

namespace Oro\Bundle\FilterBundle\Form\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Bundle\ReportBundle\Entity\Report;

class DateGroupingGroupByValidator extends ConstraintValidator
{
    /**
     * @param Report $value
     * @param Constraint $constraint
     *
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $definition = json_decode($value->getDefinition(), true);
        //if (empty($definition['date_grouping']))
        $this->context->addViolation($constraint->message);
    }
}
