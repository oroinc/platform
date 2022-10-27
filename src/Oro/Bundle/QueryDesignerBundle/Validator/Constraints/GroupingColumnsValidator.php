<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates whether a query definition created by the query designer
 * groups all columns without aggregate function if there is at least one column with aggregate function.
 */
class GroupingColumnsValidator extends ConstraintValidator
{
    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof GroupingColumns) {
            throw new UnexpectedTypeException($constraint, GroupingColumns::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof AbstractQueryDesigner) {
            throw new UnexpectedTypeException($value, AbstractQueryDesigner::class);
        }

        $definition = QueryDefinitionUtil::safeDecodeDefinition($value->getDefinition());
        if (empty($definition['columns'])) {
            return;
        }

        $missedColumnLabelsToGroup = $this->getMissedColumnLabelsToGroup($definition);
        if ($missedColumnLabelsToGroup) {
            $this->context->addViolation(
                $constraint->message,
                ['%columns%' => implode(', ', $missedColumnLabelsToGroup)]
            );
        }
    }

    /**
     * @param array $definition
     *
     * @return string[]|null
     */
    private function getMissedColumnLabelsToGroup(array $definition): ?array
    {
        $aggregateColumnNames = [];
        $notAggregateColumns = [];
        foreach ($definition['columns'] as $column) {
            if (!empty($column['func']) && 'aggregates' === $column['func']['group_type']) {
                $aggregateColumnNames[] = $column['name'];
            } else {
                $notAggregateColumns[] = $column;
            }
        }

        $missedColumnLabelsToGroup = [];
        if ($aggregateColumnNames) {
            $groupingColumnNames = array_column($definition['grouping_columns'] ?? [], 'name');
            foreach ($notAggregateColumns as $column) {
                if (!\in_array($column['name'], $groupingColumnNames, true)) {
                    $missedColumnLabelsToGroup[] = $column['label'];
                }
            }
        }

        return $missedColumnLabelsToGroup;
    }
}
