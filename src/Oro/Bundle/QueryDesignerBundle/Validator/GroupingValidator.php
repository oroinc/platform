<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

use Oro\Component\PhpUtils\ArrayUtil;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\GroupingConstraint;

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

        $groupingColumnNames = ArrayUtil::arrayColumn($groupingColumns, 'name');
        $columnNames         = ArrayUtil::arrayColumn($columns, 'name');
        $columnNamesToCheck  = array_diff(
            $columnNames,
            ArrayUtil::arrayColumn($aggregateColumns, 'name')
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
