<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiSmokeCreate;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\CheckSkippedEntityTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * @group regression
 * @dbIsolationPerTest
 */
class CreateTest extends RestJsonApiTestCase
{
    use CheckSkippedEntityTrait;

    public function testCreateWithEmptyData()
    {
        $this->runForEntities(function (string $entityClass, array $excludedActions) {
            if (in_array(ApiAction::CREATE, $excludedActions, true)) {
                return;
            }

            $entityType = $this->getEntityType($entityClass);
            if (is_a($entityClass, TestFrameworkEntityInterface::class, true)
                || str_starts_with($entityType, 'testapi')
                || $this->isSkippedEntity($entityClass, ApiAction::CREATE)
            ) {
                return;
            }

            $response = $this->post(
                ['entity' => $entityType],
                ['data' => ['type' => $entityType]],
                [],
                false
            );
            self::assertResponseStatusCodeNotEquals($response, 500);

            // test create with NULL value for read-only timestampable fields
            $this->createWithNullValueForReadOnlyTimestampableFields($entityClass, $entityType);
        });
    }

    public function createWithNullValueForReadOnlyTimestampableFields(string $entityClass, string $entityType): void
    {
        $entityConfig = $this->getApiConfig($entityClass, ApiAction::CREATE);
        if (null === $entityConfig) {
            return;
        }

        $readOnlyTimestampableFields = $this->getReadOnlyTimestampableFields($entityConfig);
        if (!$readOnlyTimestampableFields) {
            return;
        }

        $data = ['data' => ['type' => $entityType]];
        foreach ($readOnlyTimestampableFields as $fieldName) {
            $data['data']['attributes'][$fieldName] = null;
        }

        $response = $this->post(
            ['entity' => $entityType],
            $data,
            [],
            false
        );
        self::assertResponseStatusCodeNotEquals($response, 500);
    }

    private function getReadOnlyTimestampableFields(EntityDefinitionConfig $config): array
    {
        $readOnlyTimestampableFields = [];
        foreach (['createdAt', 'updatedAt'] as $fieldName) {
            if ($this->hasReadOnlyTimestampableField($config, $fieldName)) {
                $readOnlyTimestampableFields[] = $fieldName;
            }
        }

        return $readOnlyTimestampableFields;
    }

    private function hasReadOnlyTimestampableField(EntityDefinitionConfig $config, string $fieldName): bool
    {
        $field = $config->getField($fieldName);
        if (null === $field || $field->isExcluded()) {
            return false;
        }

        $formOptions = $field->getFormOptions();

        return $formOptions && isset($formOptions['mapped']) && !$formOptions['mapped'];
    }
}
