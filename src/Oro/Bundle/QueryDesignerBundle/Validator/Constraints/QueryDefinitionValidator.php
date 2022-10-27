<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator\Constraints;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates whether a query definition created by the query designer uses allowed entities and fields.
 */
class QueryDefinitionValidator extends ConstraintValidator
{
    /** @var ConfigProvider */
    private $entityConfigProvider;

    /** @var EntityFieldProvider */
    private $fieldProvider;

    public function __construct(
        ConfigProvider $entityConfigProvider,
        EntityFieldProvider $fieldProvider
    ) {
        $this->entityConfigProvider = $entityConfigProvider;
        $this->fieldProvider = $fieldProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof QueryDefinition) {
            throw new UnexpectedTypeException($constraint, QueryDefinition::class);
        }

        if (null === $value) {
            return;
        }

        if (!$value instanceof AbstractQueryDesigner) {
            throw new UnexpectedTypeException($value, AbstractQueryDesigner::class);
        }

        $rootEntityClass = $value->getEntity();
        // validate if the root class is accessible
        if (!$this->entityConfigProvider->hasConfig($rootEntityClass)) {
            $this->addClassViolation($rootEntityClass, $constraint);

            return;
        }

        // validate definition field identifiers
        $columnNamesToCheck = $this->getColumnNamesToCheck(
            QueryDefinitionUtil::safeDecodeDefinition($value->getDefinition())
        );
        foreach ($columnNamesToCheck as $columnName) {
            $this->validateColumns($rootEntityClass, $columnName, $constraint);
        }
    }

    private function validateColumns(
        string $rootEntityClass,
        string $columnName,
        QueryDefinition $constraint
    ): void {
        $joinIdHelper = new JoinIdentifierHelper($rootEntityClass);
        $joinIdentifiers = $joinIdHelper->splitJoinIdentifier($columnName);
        foreach ($joinIdentifiers as $identifier) {
            $entityClass = $joinIdHelper->getEntityClassName($identifier);
            $fieldName = $joinIdHelper->getFieldName($identifier);
            $joinFields = $this->fieldProvider->getEntityFields(
                $entityClass,
                EntityFieldProvider::OPTION_WITH_RELATIONS
                | EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS
            );

            // check if class is accessible
            if (!$joinFields && $entityClass !== $rootEntityClass) {
                $this->addClassViolation($entityClass, $constraint);
                continue;
            }

            // check if field is accessible
            if (!$this->isColumnAccessible($fieldName, $joinFields)) {
                $this->addColumnViolation($entityClass, $fieldName, $constraint);
            }
        }
    }

    private function isColumnAccessible(string $columnName, array $fields): bool
    {
        foreach ($fields as $field) {
            if ($field['name'] === $columnName) {
                return true;
            }
        }

        return false;
    }

    private function addColumnViolation(string $className, string $columnName, QueryDefinition $constraint): void
    {
        $this->context->buildViolation($constraint->messageColumn)
            ->setParameter('%className%', $className)
            ->setParameter('%columnName%', $columnName)
            ->addViolation();
    }

    private function addClassViolation(string $className, QueryDefinition $constraint): void
    {
        $this->context->buildViolation($constraint->message)
            ->setParameter('%className%', $className)
            ->addViolation();
    }

    /**
     * @param array $definition
     *
     * @return string[]
     */
    private function getColumnNamesToCheck(array $definition): array
    {
        $identifiers = [];
        if (!empty($definition['columns'])) {
            foreach ($definition['columns'] as $column) {
                $identifiers[] = $column['name'];
            }
        }
        if (!empty($definition['filters'])) {
            foreach ($definition['filters'] as $filter) {
                if (\is_array($filter) && !empty($filter['columnName'])) {
                    $identifiers[] = $filter['columnName'];
                }
            }
        }
        if (!empty($definition['grouping_columns'])) {
            foreach ($definition['grouping_columns'] as $grouping) {
                $identifiers[] = $grouping['name'];
            }
        }

        return array_unique($identifiers);
    }
}
