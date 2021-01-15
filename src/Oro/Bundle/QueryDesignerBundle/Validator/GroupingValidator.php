<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\GroupingConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates AbstractQueryDesigner::definition
 * Check if column names of definition.columns which have no ['func'] value
 * appear in definition.grouping_columns
 */
class GroupingValidator extends ConstraintValidator
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param AbstractQueryDesigner         $value
     * @param GroupingConstraint|Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $definition = json_decode($value->getDefinition(), true);

        if (empty($definition['columns'])) {
            return;
        }

        $columns = $definition['columns'];
        if (empty($definition['columns'])) {
            return;
        }

        $missedColumnLabelsToGroup = $this->getMissedColumnLabelsToGroup($definition);
        if ($missedColumnLabelsToGroup) {
            $this->context->addViolation($this->translator->trans(
                $constraint->message,
                ['%columns%' => implode(', ', $missedColumnLabelsToGroup)]
            ));
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
