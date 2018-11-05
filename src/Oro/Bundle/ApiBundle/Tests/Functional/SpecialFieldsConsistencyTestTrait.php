<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * Tests the following rules to make sure that all API resources are consistent:
 * * "created" field should be represented in API as "createdAt"
 * * "updated" field should be represented in API as "updatedAt"
 * It is expected that a test is used this trait is expended from RestApiTestCase.
 * @see \Oro\Bundle\ApiBundle\Tests\Functional\RestApiTestCase
 */
trait SpecialFieldsConsistencyTestTrait
{
    /**
     * @param string $entityClass
     * @param string $entityType
     *
     * @return bool
     */
    private function isSkippedEntity($entityClass, $entityType)
    {
        return
            is_a($entityClass, TestFrameworkEntityInterface::class, true)
            || 0 === strpos($entityType, 'testapi')
            || 0 === strpos($entityClass, ExtendHelper::ENTITY_NAMESPACE);
    }

    private function checkSpecialFieldsConsistency()
    {
        $errors = [];

        $entities = $this->getEntities();
        foreach ($entities as $entity) {
            list($entityClass, $excludedActions) = $entity;
            $entityType = $this->getEntityType($entityClass);
            if ($this->isSkippedEntity($entityClass, $entityType)) {
                continue;
            }

            $error = $this->checkSpecialFieldsConsistencyForEntity($entityClass, $excludedActions);
            if ($error) {
                $errors[] = $error;
            }
        }

        if (!empty($errors)) {
            self::fail(implode("\n", $errors));
        }
    }

    /**
     * @param string $entityClass
     * @param array  $excludedActions
     *
     * @return string|null
     */
    private function checkSpecialFieldsConsistencyForEntity(string $entityClass, array $excludedActions): ?string
    {
        $errors = [];

        $actionsToCheck = [ApiActions::GET, ApiActions::GET_LIST, ApiActions::CREATE, ApiActions::UPDATE];
        foreach ($actionsToCheck as $action) {
            if (in_array($action, $excludedActions, true)) {
                continue;
            }

            $messages = [];
            $metadata = $this->getApiMetadata($entityClass, $action);
            $this->checkFieldName($messages, $metadata, 'created', DataType::DATETIME, 'createdAt');
            $this->checkFieldName($messages, $metadata, 'updated', DataType::DATETIME, 'updatedAt');
            foreach ($messages as $message) {
                if (!isset($errors[$message])) {
                    $errors[$message] = [];
                }
                $errors[$message][] = $action;
            }
        }

        $result = null;
        if (!empty($errors)) {
            $output = [$entityClass];
            foreach ($errors as $message => $actions) {
                $output[] = sprintf('  %s. Actions: %s', $message, implode(', ', $actions));
            }
            $result = implode("\n  ", $output);
        }

        return $result;
    }

    /**
     * @param array          $errorMessages
     * @param EntityMetadata $metadata
     * @param string         $fieldName
     * @param string         $dataType
     * @param string         $expectedFieldName
     */
    private function checkFieldName(
        array &$errorMessages,
        EntityMetadata $metadata,
        string $fieldName,
        string $dataType,
        string $expectedFieldName
    ): void {
        $field = $metadata->getField($fieldName);
        if (null !== $field
            && $fieldName === $field->getPropertyPath()
            && $dataType === $field->getDataType()
            && !$metadata->hasProperty($expectedFieldName)
        ) {
            $errorMessages[] = sprintf(
                'The field "%s" should be renamed to "%s"',
                $fieldName,
                $expectedFieldName
            );
        }
    }
}
