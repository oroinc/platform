<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DurationValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value) || !$this->isValidDuration($value)) {
            $this->context->addViolation(
                $constraint->message,
                [
                    '{{ value }}' => $this->formatValue($value),
                ]
            );
        }
    }

    /**
     * @param string $duration
     *
     * @return bool
     */
    protected function isValidDuration($duration)
    {
        $regexJIRAFormat =
            '/^' .
            '(?:(?:(\d+(?:\.\d)?)?)h(?:[\s]*|$))?' .
            '(?:(?:(\d+(?:\.\d)?)?)m(?:[\s]*|$))?' .
            '(?:(?:(\d+(?:\.\d)?)?)s)?' .
            '$/i';
        $regexColumnFormat =
            '/^' .
            '((\d{1,3}:)?\d{1,3}:)?\d{1,3}' .
            '$/i';

        return preg_match($regexJIRAFormat, $duration) || preg_match($regexColumnFormat, $duration);
    }
}
