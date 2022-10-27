<?php

namespace Oro\Bundle\SegmentBundle\Validator\Constraints;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates whether a segment query definition created by the query designer
 * has at least one column with exactly specified sorting when the records limit is specified.
 */
class NotEmptySortingValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotEmptySorting) {
            throw new UnexpectedTypeException($constraint, NotEmptySorting::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof Segment) {
            throw new UnexpectedTypeException($value, Segment::class);
        }

        if (!$value->getRecordsLimit()) {
            return;
        }

        $definition = QueryDefinitionUtil::safeDecodeDefinition($value->getDefinition());
        if (empty($definition['columns'])) {
            return;
        }

        if (!$this->hasSortingColumn($definition['columns'])) {
            $this->context->addViolation($constraint->message);
        }
    }

    private function hasSortingColumn(array $columns): bool
    {
        foreach ($columns as $column) {
            if (isset($column['sorting']) && $column['sorting']) {
                return true;
            }
        }

        return false;
    }
}
