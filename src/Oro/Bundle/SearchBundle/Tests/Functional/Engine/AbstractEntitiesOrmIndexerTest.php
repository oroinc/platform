<?php

declare(strict_types=1);

namespace Oro\Bundle\SearchBundle\Tests\Functional\Engine;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DistributionBundle\Test\PackageBundleHelper;
use Oro\Bundle\EntityExtendBundle\EntityPropertyInfo;
use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\SearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\SearchBundle\Entity\Item as IndexItem;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Abstract test that verifies searchable entities can be indexed without type casting errors.
 * This test catches configuration errors like incorrect target_type in search.yml files.
 *
 * Extend this class in each package that has searchable entities and load appropriate fixtures
 * for all searchable entities and set some non-empty values for all their searchable fields.
 *
 * @group search
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
abstract class AbstractEntitiesOrmIndexerTest extends WebTestCase
{
    use SearchExtensionTrait;

    /** @var array<string, string> */
    private array $indexingErrors = [];

    /** @var array<object> */
    protected array $testEntities = [];

    /**
     * Simply delete this method after upgrade to PHPUnit 10
     */
    public function addInfo(string $message): void
    {
        echo $message;
    }

    /**
     * Return an array of entity classes to be tested.
     * Override this method to specify which entities in the package should be tested.
     *
     * @return array<string>
     */
    abstract protected function getSearchableEntityClassesToTest(): array;

    /**
     * Return an array of fields that should be excluded from empty value validation.
     * These are typically fields that are populated by event listeners during indexing
     * (e.g., via PrepareEntityMapEvent) rather than being read from entity properties.
     *
     * Format: ['EntityClass' => ['field1', 'field2'], ...]
     *
     * @return array<string, array<string>>
     */
    protected function getFieldsToExcludeFromValidation(): array
    {
        return [];
    }

    /**
     * Register an entity created for testing purposes.
     * Only registered entities will be checked for empty searchable fields.
     */
    protected function persistTestEntity(object $entity): void
    {
        $manager = $this->getDoctrine()->getManagerForClass(\get_class($entity));
        $manager->persist($entity);
        $this->testEntities[] = $entity;
    }

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $engine = self::getContainer()->get('oro_search.engine.parameters')->getEngineName();
        if ($engine !== 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }

        $this->indexingErrors = [];
        $this->testEntities = [];
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->clearIndexTextTable();
    }

    /**
     * Tests that searchable entities can be indexed without type casting errors.
     * This test would catch configuration errors like incorrect target_type in search.yml files.
     * For example, if a string field is configured as a datetime type, this test would fail.
     */
    public function testSearchableEntitiesCanBeIndexed(): void
    {
        /** @var AbstractIndexer $indexer */
        $indexer = self::getSearchIndexer();
        $entitiesToTest = $this->getSearchableEntityClassesToTest();

        // Filter to only test specified entities if provided
        $classes = \array_intersect($indexer->getClassesForReindex(), $entitiesToTest);

        $this->assertNotEmpty($classes, 'There should be at least one searchable entity to test');

        // Collect all validation errors
        $allErrors = [];

        // Check 1: Verify that getEntitiesToTest() returns all searchable entities in the package
        $coverageErrors = $this->checkTestCoversAllSearchableEntities($entitiesToTest);
        if (!empty($coverageErrors)) {
            $allErrors[] = $coverageErrors;
        }

        // Check 2: Verify that all searchable fields in persisted entities are not empty
        $emptyFieldsErrors = $this->checkSearchableFieldsAreNotEmpty($classes);
        if (!empty($emptyFieldsErrors)) {
            $allErrors[] = $emptyFieldsErrors;
        }

        // Check 3: Try to index entities and capture type casting errors
        $logger = $this->createErrorCapturingLogger();
        $indexer->setLogger($logger);

        $indexedClasses = [];

        foreach ($classes as $entityClass) {
            // Try to index the entity class
            try {
                $indexer->reindex($entityClass);
                $indexedClasses[] = $entityClass;
            } catch (\Throwable $e) {
                $this->indexingErrors[$entityClass] = $e->getMessage();
            }
        }

        if (!empty($this->indexingErrors)) {
            $allErrors[] = \sprintf(
                "The following entities have indexing errors:\n%s",
                $this->formatErrors($this->indexingErrors)
            );
        }

        // Report all errors together
        $this->assertEmpty(
            $allErrors,
            "\n\n" . \implode("\n\n", $allErrors)
        );

        // Verify at least some entities were indexed
        $this->assertNotEmpty(
            $indexedClasses,
            'At least some entities should have been indexed'
        );

        // Verify indexed data exists
        $indexItemRepository = $this->getDoctrine()
            ->getManagerForClass(IndexItem::class)
            ->getRepository(IndexItem::class);

        $totalIndexedItems = \count($indexItemRepository->findAll());
        $this->assertGreaterThan(
            0,
            $totalIndexedItems,
            'At least some index items should have been created'
        );
    }

    protected function getDoctrine(): ManagerRegistry
    {
        return $this->getContainer()->get('doctrine');
    }

    /**
     * Checks that getEntitiesToTest() returns all searchable entities defined in the package's search configuration.
     * Returns an error message if there are missing entities, null otherwise.
     */
    private function checkTestCoversAllSearchableEntities(?array $entitiesToTest): ?string
    {
        if ($entitiesToTest === null) {
            // Test covers all entities, nothing to check
            return null;
        }

        $searchableEntitiesInPackage = $this->getSearchableEntitiesInPackage();

        $missingEntities = \array_diff($searchableEntitiesInPackage, $entitiesToTest);

        if (empty($missingEntities)) {
            return null;
        }

        return \sprintf(
            "The following searchable entities are defined in search configuration but not returned by "
            . "getEntitiesToTest():\n"
            . "%s\n"
            . "\n"
            . "Please add these entities to getEntitiesToTest() and create test data for them in setUp().",
            '  - ' . \implode("\n  - ", $missingEntities)
        );
    }

    /**
     * Checks that all searchable fields in persisted entities have non-empty values.
     * Returns an error message if there are empty fields, null otherwise.
     * Only checks entities that have been registered via addTestEntity().
     */
    private function checkSearchableFieldsAreNotEmpty(array $classes): ?string
    {
        // Skip check if no test entities were registered
        if (empty($this->testEntities)) {
            return null;
        }

        /** @var AbstractSearchMappingProvider $mappingProvider */
        $mappingProvider = $this->getContainer()->get('oro_search.provider.search_mapping');

        $emptyFieldsByEntity = [];

        foreach ($classes as $entityClass) {
            $entityConfig = $mappingProvider->getEntityConfig($entityClass);
            if (empty($entityConfig['fields'])) {
                continue;
            }

            $searchableFields = \array_keys($entityConfig['fields']);
            $emptyFields = $this->getEmptySearchableFieldsFromTestEntities($entityClass, $searchableFields);

            if (!empty($emptyFields)) {
                $emptyFieldsByEntity[$entityClass] = $emptyFields;
            }
        }

        if (empty($emptyFieldsByEntity)) {
            return null;
        }

        return \sprintf(
            "The following entities have empty searchable fields in test data:\n%s\n\n" .
            "Please ensure all searchable fields have non-empty values in setUp() to properly test type casting.",
            $this->formatEmptyFields($emptyFieldsByEntity)
        );
    }

    /**
     * Gets all searchable entities defined in the current package's search configuration.
     */
    private function getSearchableEntitiesInPackage(): array
    {
        /** @var AbstractSearchMappingProvider $mappingProvider */
        $mappingProvider = $this->getContainer()->get('oro_search.provider.search_mapping');
        $allSearchableEntities = $mappingProvider->getEntityClasses();

        // Get bundle namespaces by traversing up from the test class
        $bundleNamespaces = PackageBundleHelper::getPackageBundleNamespacesFromTestClass($this);

        // Filter entities that belong to any bundle in this package
        return \array_filter($allSearchableEntities, static function ($entityClass) use ($bundleNamespaces) {
            foreach ($bundleNamespaces as $namespace) {
                if (\str_starts_with($entityClass, $namespace)) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Gets empty searchable fields for a given entity class from registered test entities.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getEmptySearchableFieldsFromTestEntities(string $entityClass, array $searchableFields): array
    {
        $manager = $this->getDoctrine()->getManagerForClass($entityClass);
        if (!$manager instanceof EntityManagerInterface) {
            return [];
        }

        // Filter test entities to only those of the specified class
        $entities = \array_filter($this->testEntities, static fn ($entity) => \is_a($entity, $entityClass));

        if (empty($entities)) {
            // No test entities registered for this class - skip check
            return [];
        }

        // Get fields to exclude from validation (e.g., fields populated by event listeners)
        $excludedFields = $this->getFieldsToExcludeFromValidation();
        $fieldsToExcludeForEntity = $excludedFields[$entityClass] ?? [];

        // Get the mapper and property accessor to check field accessibility
        $mapper = $this->getContainer()->get('oro_search.mapper');
        $propertyAccessor = $this->getContainer()->get('property_accessor');
        $emptyFields = [];
        $nonExistentFields = [];

        // Check each registered test entity for empty fields
        foreach ($entities as $entity) {
            foreach ($searchableFields as $fieldName) {
                // Skip non-string field names (shouldn't happen but be defensive)
                if (!\is_string($fieldName)) {
                    continue;
                }

                // Skip special fields
                if (\in_array($fieldName, ['all_text', 'all_text_LOCALIZATION_ID'], true)) {
                    continue;
                }

                // Skip fields that are excluded from validation
                if (\in_array($fieldName, $fieldsToExcludeForEntity, true)) {
                    $this->addInfo(\sprintf(
                        "  [EXCLUDED] %s::%s (excluded from validation)\n",
                        $entityClass,
                        $fieldName
                    ));
                    continue;
                }

                // Check if field is accessible (exists on entity)
                // Use the same logic as AbstractMapper::getFieldValue()
                $isAccessible = $this->isFieldAccessible($entity, $fieldName, $propertyAccessor);

                if (!$isAccessible) {
                    // Field doesn't exist on entity - this is a configuration error
                    $nonExistentFields[$fieldName] = true;
                    $this->addInfo(\sprintf(
                        "  [CONFIG ERROR] %s::%s (field does not exist on entity)\n",
                        $entityClass,
                        $fieldName
                    ));
                    continue;
                }

                // Use the same logic as the indexer to get field value
                // This handles extended entity fields correctly
                $value = $mapper->getFieldValue($entity, $fieldName);

                // Check if the value is empty
                if ($this->isValueEmpty($value)) {
                    $emptyFields[$fieldName] = true;
                }
            }
        }

        // Return both empty fields and non-existent fields
        return \array_keys(\array_merge($emptyFields, $nonExistentFields));
    }

    /**
     * Checks if a value is considered empty for search indexing purposes.
     */
    private function isValueEmpty(mixed $value): bool
    {
        // Handle null, empty string, empty array
        if ($value === null || $value === '' || $value === []) {
            return true;
        }

        // Handle LocalizedFallbackValue objects - check if they have any content
        if ($value instanceof LocalizedFallbackValue) {
            return $value->getString() === null && $value->getText() === null;
        }
        if ($value instanceof AbstractLocalizedFallbackValue && \method_exists($value, 'getWysiwyg')) {
            return $value->getWysiwyg() === null;
        }

        return false;
    }

    private function formatEmptyFields(array $emptyFieldsByEntity): string
    {
        $formatted = [];
        foreach ($emptyFieldsByEntity as $entityClass => $fields) {
            $formatted[] = "  - $entityClass: " . \implode(', ', $fields);
        }
        return \implode("\n", $formatted);
    }

    private function createErrorCapturingLogger(): LoggerInterface
    {
        return new class ($this->indexingErrors) implements LoggerInterface {
            /** @noinspection PhpPropertyOnlyWrittenInspection */
            public function __construct(private array &$errors)
            {
            }

            public function log($level, $message, array $context = []): void
            {
                if ($level === LogLevel::ERROR && \str_contains($message, 'have wrong type')) {
                    $this->errors['type_casting_error'] = $message;
                }
            }

            public function emergency($message, array $context = []): void
            {
            }

            public function alert($message, array $context = []): void
            {
            }

            public function critical($message, array $context = []): void
            {
            }

            public function error($message, array $context = []): void
            {
                $this->log(LogLevel::ERROR, $message, $context);
            }

            public function warning($message, array $context = []): void
            {
            }

            public function notice($message, array $context = []): void
            {
            }

            public function info($message, array $context = []): void
            {
            }

            public function debug($message, array $context = []): void
            {
            }
        };
    }

    private function formatErrors(array $errors): string
    {
        $formatted = [];
        foreach ($errors as $class => $error) {
            $formatted[] = "  - $class: $error";
        }
        return \implode("\n", $formatted);
    }

    /**
     * Checks if a field is accessible on an entity.
     * Uses the same logic as AbstractMapper::getFieldValue() to determine accessibility.
     */
    private function isFieldAccessible(
        object $entity,
        string $fieldName,
        PropertyAccessorInterface $propertyAccessor
    ): bool {
        // Check if getter method exists (same logic as AbstractMapper)
        $getter = sprintf('get%s', $fieldName);
        if (EntityPropertyInfo::methodExists($entity, $getter)) {
            return true;
        }

        // Check if property is readable via PropertyAccessor
        try {
            return $propertyAccessor->isReadable($entity, $fieldName);
        } catch (\Exception $e) {
            return false;
        }
    }
}
