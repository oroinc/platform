<?php

namespace Oro\Bundle\FormBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Validates that the given value is a valid related entity.
 */
class RelatedEntityValidator extends EntityClassValidator
{
    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        if (empty($value)) {
            return;
        }

        if (!is_array($value) || empty($value['id']) || empty($value['entity'])) {
            return;
        }

        if ($this->isValidEntityClass($value['entity'])) {
            $this->context->addViolation($constraint->message, [
                '{{ value }}' => $this->formatValue($value),
            ]);
        }
    }

    #[\Override]
    protected function formatValue($value, $format = 0): string
    {
        if (is_array($value) && array_key_exists('id', $value) && array_key_exists('entity', $value)) {
            return sprintf(
                '[id => %s, entity => %s]',
                $this->formatValue($value['id']),
                $this->formatValue($value['entity'])
            );
        }

        return parent::formatValue($value, $format);
    }
}
