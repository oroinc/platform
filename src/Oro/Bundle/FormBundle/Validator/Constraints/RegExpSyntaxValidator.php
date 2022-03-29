<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Oro\Bundle\FormBundle\Utils\RegExpUtils;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

/**
 * Validates a regular expression syntax.
 */
class RegExpSyntaxValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof RegExpSyntax) {
            throw new UnexpectedTypeException($constraint, RegExpSyntax::class);
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_scalar($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $value = '~' . $value . '~i';

        $lastError = RegExpUtils::validateRegExp($value);
        if ($lastError !== null) {
            $this->context
                ->buildViolation($constraint->message)
                ->setParameter('{{ reason }}', $this->formatValue($lastError))
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(RegExpSyntax::INVALID_REGEXP_SYNTAX_ERROR)
                ->addViolation();
        }
    }
}
