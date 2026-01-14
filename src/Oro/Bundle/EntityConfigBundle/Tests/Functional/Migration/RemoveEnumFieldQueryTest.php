<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\LockMode;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityRepository;
use Extend\Entity\TestEntity1;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveEnumFieldQuery;
use Oro\Bundle\EntityConfigBundle\Tests\Functional\DataFixtures\LoadEnumOptionsData;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RemoveEnumFieldQueryTest extends WebTestCase
{
    private Connection $connection;
    private ArrayLogger $logger;
    private EntityRepository $repository;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();
        $this->loadFixtures([LoadEnumOptionsData::class]);

        $doctrine = self::getContainer()->get('doctrine');

        $this->connection = $doctrine->getConnection();
        $this->repository = $doctrine->getRepository(TestEntity1::class);
        $this->logger = new ArrayLogger();
    }

    public function testGetDescription(): void
    {
        $entityField = 'testEnumField';

        $migrationQuery = $this->createMigrationQuery(TestEntity1::class, $entityField);

        self::assertEquals(
            sprintf('Remove %s enum field data', $entityField),
            $migrationQuery->getDescription()
        );
    }

    public function testEntityNotFound(): void
    {
        $entityClass = \Extend\Entity\UnknownEntity::class;
        $entityField = 'testEnumField';

        $migrationQuery = $this->createMigrationQuery($entityClass, $entityField);
        $migrationQuery->execute($this->logger);

        self::assertSame(
            [
                sprintf("Enum field '%s' from Entity '%s' is not found", $entityField, $entityClass)
            ],
            $this->logger->getMessages()
        );
    }

    public function testFieldNotFound(): void
    {
        $entityClass = TestEntity1::class;
        $entityField = 'unknownEnumField';

        $migrationQuery = $this->createMigrationQuery($entityClass, $entityField);
        $migrationQuery->execute($this->logger);

        self::assertSame(
            [
                sprintf("Enum field '%s' from Entity '%s' is not found", $entityField, $entityClass)
            ],
            $this->logger->getMessages()
        );
    }

    /**
     * @dataProvider enumFieldData
     */
    public function testExecute(string $entityClass, string $entityField, ?string $preservedEnumField): void
    {
        $fieldRow = $this->getFieldRow($entityClass, $entityField);
        $data = $this->connection->convertToPHPValue($fieldRow['data'], Types::ARRAY);
        $enumCode = $data['enum']['enum_code'];
        $enumOptions = $this->getEnumOptions($enumCode);

        $migrationQuery = $this->createMigrationQuery($entityClass, $entityField);
        $migrationQuery->execute($this->logger);

        self::assertFalse($this->getEnumOptions($enumCode));
        self::assertFalse($this->getFieldRow($entityClass, $entityField));

        if ($enumOptions) {
            if (!array_is_list($enumOptions)) {
                $enumOptions = [$enumOptions];
            }
            foreach ($enumOptions as $enumOption) {
                $translationKey = ExtendHelper::buildEnumOptionTranslationKey($enumOption['id']);
                self::assertFalse($this->getEnumOptionTranslation($enumOption['id']));
                self::assertFalse($this->getOroTranslationKey($translationKey));
            }
        }

        $idToCheck = $this->getReference(LoadEnumOptionsData::REFERENCE_KEY)
            ->getId();
        $testEntity = $this->repository->find($idToCheck, LockMode::NONE);

        self::assertEmpty($testEntity->$entityField);
        if (null !== $preservedEnumField) {
            self::assertNotEmpty($testEntity->$preservedEnumField);
        }
    }

    public function enumFieldData(): array
    {
        return [
            'common enum field' => [
                'entityClass' => TestEntity1::class,
                'entityField' => 'testEnumField',
                'fieldEnumToPreserve' => 'testMultienumField',
            ],
            'multi-enum field' => [
                'entityClass' => TestEntity1::class,
                'entityField' => 'testMultienumField',
                'fieldEnumToPreserve' => null,
            ]
        ];
    }

    private function getEntityRow(string $entityClass): array|bool
    {
        $getEntitySql = 'SELECT e.id, e.data 
                FROM oro_entity_config as e 
                WHERE e.class_name = ? 
                LIMIT 1';

        return $this->connection->fetchAssociative(
            $getEntitySql,
            [$entityClass]
        );
    }

    private function getEnumOptionTranslation(string $enumOptionId): array|bool
    {
        $getEntitySql = 'SELECT * FROM oro_enum_option_trans WHERE foreign_key = ?';

        return $this->connection->fetchAssociative(
            $getEntitySql,
            [$enumOptionId]
        );
    }

    private function getOroTranslationKey(string $translationKey): array|bool
    {
        $getEntitySql = 'SELECT *  FROM oro_translation_key WHERE key = ?';

        return $this->connection->fetchAssociative(
            $getEntitySql,
            [$translationKey]
        );
    }

    private function getEnumOptions(string $enumCode): array|bool
    {
        $getEntitySql = 'SELECT id FROM oro_enum_option WHERE enum_code = ?';

        return $this->connection->fetchAssociative(
            $getEntitySql,
            [$enumCode]
        );
    }

    private function getFieldRow(string $entityClass, string $entityField): array|bool
    {
        $getFieldSql = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?
            LIMIT 1';

        return $this->connection->fetchAssociative(
            $getFieldSql,
            [
                $entityClass,
                $entityField
            ]
        );
    }

    private function createMigrationQuery(string $entityClass, string $entityField): RemoveEnumFieldQuery
    {
        $migrationQuery = new RemoveEnumFieldQuery($entityClass, $entityField);
        $migrationQuery->setConnection($this->connection);
        $migrationQuery->setContainer(self::getContainer());

        return $migrationQuery;
    }
}
