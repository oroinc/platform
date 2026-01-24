<?php

namespace Oro\Bundle\CurrencyBundle\Validator\Constraints;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CurrencyBundle\Validator\Constraints;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator for the {@see OptionalPrice} constraint.
 *
 * This validator ensures that {@see Price} entities maintain data integrity by requiring
 * a currency to be specified whenever a monetary value is present. It prevents the
 * creation of incomplete price data where a value exists without its associated
 * currency, which would be meaningless in a multi-currency system.
 */
class OptionalPriceValidator extends ConstraintValidator
{
    /**
     *
     * @param Price $price
     * @param Constraint $constraint
     */
    #[\Override]
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
