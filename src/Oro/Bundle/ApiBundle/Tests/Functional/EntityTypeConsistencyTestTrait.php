<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\TestFrameworkBundle\Entity\TestFrameworkEntityInterface;

/**
 * Tests that all API resources has valid aliases.
 * It is expected that a test is used this trait is expended from RestApiTestCase.
 * @see \Oro\Bundle\ApiBundle\Tests\Functional\RestApiTestCase
 */
trait EntityTypeConsistencyTestTrait
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
            || 0 === strpos($entityType, 'testapi');
    }

    private function checkEntityTypeConsistency()
    {
        $errors = [];

        $entities = $this->getEntities();
        foreach ($entities as $entity) {
            list($entityClass) = $entity;
            $entityType = $this->getEntityType($entityClass);
            if ($this->isSkippedEntity($entityClass, $entityType)) {
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

    /**
     * @param string $entityType
     *
     * @return bool
     */
    private function isValidEntityType(string $entityType): bool
    {
        return 1 === preg_match('/^[a-z]+[0-9a-z]*$/', $entityType);
    }

    /**
     * @return string
     */
    private function getValidEntityTypeMessage(): string
    {
        return 'It should contain only lowercase alphabetic symbols and numbers.';
    }
}
