<?php

/*
 * This file is a copy of {@see Symfony\Component\Validator\Constraints\AllValidator}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Bundle\ApiBundle\Validator\Constraints;

use Doctrine\ORM\PersistentCollection;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * The difference with Symfony constraint is that uninitialized PersistentCollection is not validated.
 * @see Symfony\Component\Validator\Constraints\All
 * @see Symfony\Component\Validator\Constraints\AllValidator
 */
class AllValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof All) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\All');
        }

        if (null === $value) {
            return;
        }

        if (!is_array($value) && !$value instanceof \Traversable) {
            throw new UnexpectedTypeException($value, 'array or Traversable');
        }

        if ($value instanceof PersistentCollection && !$value->isInitialized()) {
            // skip uninitialized PersistentCollection
            return;
        }

        /** @var ExecutionContextInterface $context */
        $context = $this->context;
        $validator = $context->getValidator()->inContext($context);
        foreach ($value as $key => $element) {
            $validator->atPath('[' . $key . ']')->validate($element, $constraint->constraints);
        }
    }
}
