<?php

namespace Oro\Bundle\CurrencyBundle\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class OptionalPriceValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     *
     * @param Price $price
     * @param Constraint $constraint
     */
    public function validate($price, Constraint $constraint)
    {
        if ($price->getValue() && !$price->getCurrency()) {
            /* @var $constraint Constraints\OptionalPrice */
            $this->context->buildViolation($constraint->message)
                ->atPath('currency')
                ->addViolation();
        }
    }
}
