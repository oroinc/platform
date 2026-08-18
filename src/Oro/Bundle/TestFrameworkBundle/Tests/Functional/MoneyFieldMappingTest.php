<?php

declare(strict_types=1);

namespace Oro\Bundle\TestFrameworkBundle\Tests\Functional;

use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\DBAL\Types\MoneyType;

/**
 * Guards every mapped decimal field of the application against redundant UPDATE queries.
 *
 * The "money" and "money_value" Doctrine types, as well as the built-in "decimal" type,
 * provide the raw decimal string of the column to PHP, e.g. "15.4100". A scalar typehint
 * on the backing property, e.g. "?float", makes PHP coerce that string during the reflection
 * assignment Doctrine performs while hydrating the entity. The unit of work keeps the original
 * string in its original entity data, so the changeset computation compares "15.4100" with
 * 15.41, considers a freshly loaded entity dirty and issues an UPDATE query on flush even
 * though nothing has been changed.
 *
 * @group schema
 */
final class MoneyFieldMappingTest extends WebTestCase
{
    /**
     * Hydration probe with trailing zeros a float value is not able to preserve.
     */
    private const string DECIMAL_PROBE = '15.4100';

    /**
     * Existing debt: "<entity class>::<field name>" pairs that are known to coerce the value
     * provided by Doctrine hydration and are therefore excluded from the assertion.
     *
     * The list is intentionally empty: it exists so that unavoidable debt is recorded explicitly
     * instead of the assertion being weakened. A newly added field must never be listed here,
     * the backing property must be fixed instead.
     */
    private const array KNOWN_VIOLATIONS = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testDecimalFieldsPreserveTheValueProvidedByDoctrineHydration(): void
    {
        $violations = $this->findViolations();

        self::assertSame(
            [],
            $violations,
            sprintf(
                "%d mapped decimal field(s) coerce the value provided by Doctrine hydration,"
                . " which makes a freshly loaded entity dirty and causes a redundant UPDATE query."
                . " Remove the scalar typehint from the backing property:\n%s",
                count($violations),
                implode("\n", $violations)
            )
        );
    }

    /**
     * @return string[]
     */
    private function findViolations(): array
    {
        $violations = [];
        foreach (self::getContainer()->get('doctrine')->getManagers() as $manager) {
            if (!$manager instanceof EntityManagerInterface) {
                continue;
            }

            foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
                $violations = array_merge($violations, $this->findEntityViolations($metadata));
            }
        }

        return array_values(array_unique($violations));
    }

    /**
     * @return string[]
     */
    private function findEntityViolations(ClassMetadata $metadata): array
    {
        // Mapped superclasses and embeddables are not hydrated on their own, their fields are
        // covered through the entities that inherit or embed them.
        if ($metadata->isMappedSuperclass || $metadata->isEmbeddedClass) {
            return [];
        }

        $violations = [];
        foreach ($metadata->fieldMappings as $fieldName => $fieldMapping) {
            if (!$this->isRawDecimalType($fieldMapping['type'])) {
                continue;
            }

            if (in_array($metadata->name . '::' . $fieldName, self::KNOWN_VIOLATIONS, true)) {
                continue;
            }

            // Dynamically added fields, e.g. extended or serialized ones, are not backed
            // by a property declaration and cannot be typehinted.
            $reflectionProperty = $metadata->reflFields[$fieldName] ?? null;
            if (null === $reflectionProperty) {
                continue;
            }

            // Entity constructors may require arguments, Doctrine instantiates entities
            // without calling a constructor as well.
            $entity = $metadata->newInstance();

            // Write and read the value exactly as Doctrine hydration does.
            $reflectionProperty->setValue($entity, self::DECIMAL_PROBE);

            if (self::DECIMAL_PROBE !== $reflectionProperty->getValue($entity)) {
                $violations[] = sprintf(
                    '%s::$%s (Doctrine type "%s", declared property type "%s")',
                    $metadata->name,
                    $fieldName,
                    $fieldMapping['type'],
                    $this->getDeclaredPropertyType($metadata, (string)$fieldName)
                );
            }
        }

        return $violations;
    }

    private function isRawDecimalType(string $type): bool
    {
        if (!Type::hasType($type)) {
            return false;
        }

        $doctrineType = Type::getType($type);

        // MoneyType covers the "money" and "money_value" types, DecimalType covers "decimal".
        return $doctrineType instanceof MoneyType || $doctrineType instanceof DecimalType;
    }

    private function getDeclaredPropertyType(ClassMetadata $metadata, string $fieldName): string
    {
        $reflectionClass = $metadata->getReflectionClass();
        while (null !== $reflectionClass) {
            if ($reflectionClass->hasProperty($fieldName)) {
                $propertyType = $reflectionClass->getProperty($fieldName)->getType();

                return null === $propertyType ? 'none' : (string)$propertyType;
            }

            $reflectionClass = $reflectionClass->getParentClass() ?: null;
        }

        return 'not declared';
    }
}
