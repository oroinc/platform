<?php

namespace Oro\Bundle\QueryDesignerBundle\Validator;

use Oro\Bundle\EntityBundle\Provider\EntityWithFieldsProvider;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\JoinIdentifierHelper;
use Oro\Bundle\QueryDesignerBundle\Validator\Constraints\DefinitionQueryConstraint;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * This validator check if the columns and entity classes are available to be used in query designer.
 */
class DefinitionQueryValidator extends ConstraintValidator
{
    /** @var EntityWithFieldsProvider */
    protected $fieldsProvider;

    /**
     * @var array The local cache of available in query designer entities and fields
     */
    protected $availableEntityFields;

    /**
     * DefinitionQueryValidator constructor.
     *
     * @param EntityWithFieldsProvider $fieldsProvider
     */
    public function __construct(
        EntityWithFieldsProvider $fieldsProvider
    ) {
        $this->fieldsProvider = $fieldsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof AbstractQueryDesigner) {
            return;
        }

        $this->availableEntityFields = $this->fieldsProvider->getFields(true, true);

        $rootClass = $value->getEntity();

        // validate if the root class is accessible
        if (!$this->isClassAvailable($rootClass)) {
            $this->addClassViolation($rootClass, $constraint);
            return;
        }

        $definition = json_decode($value->getDefinition(), true);
        // return if definition is empty
        if (null === $definition) {
            return;
        }

        // validate definition field identifiers
        $fieldsIdentifiers = $this->getDefinitionFieldIdentifiers($definition);
        foreach ($fieldsIdentifiers as $fieldIdentifier) {
            $this->validateIdentityString($rootClass, $fieldIdentifier, $constraint);
        }

        // clear the local cache to avoid side effects
        $this->availableEntityFields = [];
    }

    /**
     * @param string                               $rootClass
     * @param string                               $identifierString
     * @param Constraint|DefinitionQueryConstraint $constraint
     */
    protected function validateIdentityString($rootClass, $identifierString, Constraint $constraint)
    {
        $fieldHelper = new JoinIdentifierHelper($rootClass);
        $joinIdentifiers = explode('+', $identifierString);
        foreach ($joinIdentifiers as $identifier) {
            $fieldClass = $fieldHelper->getEntityClassName($identifier);
            $fieldName = $fieldHelper->getFieldName($identifier);

            // Check if class is Accessible
            if ($fieldClass !== $rootClass && !$this->isClassAvailable($fieldClass)) {
                $this->addClassViolation($fieldClass, $constraint);
                continue;
            }

            // Check if field is Accessible
            if (!$this->isColumnAvailable($fieldClass, $fieldName)) {
                $this->addColumnViolation($fieldClass, $fieldName, $constraint);
            }
        }
    }

    /**
     * @param string $className
     * @param string $columnName
     *
     * @return bool
     */
    protected function isColumnAvailable($className, $columnName)
    {
        $foundFieldDefinition = array_filter(
            $this->availableEntityFields[$className]['fields'],
            function ($a) use ($columnName) {
                return $a['name'] === $columnName;
            }
        );

        return !empty($foundFieldDefinition);
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    protected function isClassAvailable($className)
    {
        return array_key_exists($className, $this->availableEntityFields);
    }

    /**
     * @param string                               $className
     * @param string                               $columnName
     * @param Constraint|DefinitionQueryConstraint $constraint
     */
    protected function addColumnViolation($className, $columnName, Constraint $constraint)
    {
        $this->context->buildViolation($constraint->messageColumn)
            ->setParameter('%className%', $className)
            ->setParameter('%columnName%', $columnName)
            ->addViolation();
    }

    /**
     * @param string                               $className
     * @param Constraint|DefinitionQueryConstraint $constraint
     */
    protected function addClassViolation($className, Constraint $constraint)
    {
        $this->context->buildViolation($constraint->message)
            ->setParameter('%className%', $className)
            ->addViolation();
    }

    /**
     * @param array $definition
     *
     * @return array
     */
    protected function getDefinitionFieldIdentifiers(array $definition)
    {
        $identifiers = [];

        if (array_key_exists('columns', $definition)) {
            foreach ($definition['columns'] as $column) {
                $identifiers[] = $column['name'];
            }
        }

        if (array_key_exists('filters', $definition)) {
            foreach ($definition['filters'] as $filter) {
                if (is_array($filter) && array_key_exists('columnName', $filter)) {
                    $identifiers[] = $filter['columnName'];
                }
            }
        }

        if (array_key_exists('grouping_columns', $definition)) {
            foreach ($definition['grouping_columns'] as $grouping) {
                $identifiers[] = $grouping['name'];
            }
        }

        return array_unique($identifiers);
    }
}
