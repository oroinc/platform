<?php

namespace Oro\Bundle\ReportBundle\Validator;

use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Validator\Constraints\ReportColumnDuplicateConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class ReportColumnDuplicateValidator
 */
class ReportColumnDuplicateValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof Report) {
            return;
        }

        $definition = json_decode($value->getDefinition(), true);

        /** @var ReportColumnDuplicateConstraint $constraint */
        if (isset($definition['columns']) && $columnNames = $this->checkOnColumnDuplicate($definition['columns'])) {
            $this->context->addViolation($constraint->columnIsDuplicate, ['%columnName%' => $columnNames]);
        }
    }

    /**
     * @param array $columns
     *
     * @return bool
     */
    private function checkOnColumnDuplicate(array $columns)
    {
        $useMap = [];
        $result = [];
        foreach ($columns as $key => $value) {
            $key = is_array($value['func']) ? $value['name'].$value['func']['name'] : $value['name'].$value['func'];
            if (isset($useMap[$key])) {
                $result[] = $value['label'];
            }
            $useMap[$key] = $value['name'];
        }
        if ($result) {
            return implode($result, ', ');
        }

        return false;
    }
}
