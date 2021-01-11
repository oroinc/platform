<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that a query definition created by the query designer does not contain column duplicates.
 */
class NoColumnDuplicatesValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof NoColumnDuplicates) {
            throw new UnexpectedTypeException($constraint, NoColumnDuplicates::class);
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

        $duplicates = $this->searchDuplicates($definition['columns']);
        if ($duplicates) {
            $this->context->addViolation($constraint->message, ['%duplicates%' => implode(', ', $duplicates)]);
        }
    }

    /**
     * @param array $columns
     *
     * @return string[]
     */
    private function searchDuplicates(array $columns): array
    {
        $duplicates = [];
        $duplicateMap = [];
        foreach ($columns as $value) {
            $columnId = QueryDefinitionUtil::buildColumnIdentifier($value);
            if (isset($duplicateMap[$columnId])) {
                $duplicates[] = $value['label'];
            } else {
                $duplicateMap[$columnId] = true;
            }
        }

        return $duplicates;
    }
}
