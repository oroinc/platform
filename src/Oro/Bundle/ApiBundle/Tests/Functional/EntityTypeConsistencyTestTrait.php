<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Util\ValueNormalizerUtil;
use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * Tests that all API resources has valid aliases.
 * It is expected that a test is used this trait is expended from RestApiTestCase.
 * @see \Oro\Bundle\ApiBundle\Tests\Functional\RestApiTestCase
 */
trait EntityTypeConsistencyTestTrait
{
    private function isSkippedEntity(string $entityClass, string $entityType): bool
    {
        return
            is_a($entityClass, TestFrameworkEntityInterface::class, true)
            || str_starts_with($entityType, 'testapi');
    }

    private function checkEntityTypeConsistency()
    {
        $errors = [];

        $entities = $this->getEntities();
        foreach ($entities as $entity) {
            [$entityClass] = $entity;
            $entityType = ValueNormalizerUtil::tryConvertToEntityType(
                $this->getValueNormalizer(),
                $entityClass,
                $this->getRequestType()
            );
            if (!$entityType || $this->isSkippedEntity($entityClass, $entityType)) {
                continue;
            }

            if (!$this->isValidEntityType($entityType)) {
                $errors[] = sprintf(
                    'Invalid entity type "%s" for "%s". %s',
                    $entityType,
                    $entityClass,
                    $this->getValidEntityTypeMessage()
                );
            }
        }

        if (!empty($errors)) {
            self::fail(implode("\n", $errors));
        }
    }

    private function isValidEntityType(string $entityType): bool
    {
        return 1 === preg_match('/^[a-z]+[0-9a-z]*$/', $entityType);
    }

    private function getValidEntityTypeMessage(): string
    {
        return 'It should contain only lowercase alphabetic symbols and numbers.';
    }
}
