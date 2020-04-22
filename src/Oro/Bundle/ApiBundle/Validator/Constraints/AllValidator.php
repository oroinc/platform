<?php

/*
 * This file is a copy of {@see Symfony\Component\Validator\Constraints\AllValidator}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * This validator allows to apply a collection of constraints to each element of the array or Traversable object.
 * The difference with Symfony constraint is that uninitialized lazy collection is not validated.
 * @see \Symfony\Component\Validator\Constraints\All
 * @see \Symfony\Component\Validator\Constraints\AllValidator
 */
class AllValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof All) {
            throw new UnexpectedTypeException($constraint, All::class);
        }

        if (null === $value) {
            return;
        }

        if (!\is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedTypeException($value, 'iterable');
        }

        if ($value instanceof AbstractLazyCollection && !$value->isInitialized()) {
            return;
        }

        $context = $this->context;
        $validator = $context->getValidator()->inContext($context);
        foreach ($value as $key => $element) {
            $validator->atPath('[' . $key . ']')->validate($element, $constraint->constraints);
        }
    }
}
