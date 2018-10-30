<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\GroupingConstraint;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

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

        $aggregateColumns = array_filter(
            $columns,
            function (array $column) {
                return !empty($column['func']);
            }
        );

        if (empty($aggregateColumns)) {
            return;
        }

        $groupingColumns = [];
        if (!empty($definition['grouping_columns'])) {
            $groupingColumns = $definition['grouping_columns'];
        }

        $groupingColumnNames = \array_column($groupingColumns, 'name');
        $columnNames         = \array_column($columns, 'name');
        $columnNamesToCheck  = array_diff(
            $columnNames,
            \array_column($aggregateColumns, 'name')
        );
        $columnsToGroup      = array_diff($columnNamesToCheck, $groupingColumnNames);

        if (empty($columnsToGroup)) {
            return;
        }

        $columnLabels = [];
        foreach ($columns as $column) {
            if (in_array($column['name'], $columnsToGroup)) {
                $columnLabels[] = $column['label'];
            }
        }

        $this->context->addViolation(
            $this->translator->trans($constraint->message, ['%columns%' => implode(', ', $columnLabels)])
        );
    }
}
