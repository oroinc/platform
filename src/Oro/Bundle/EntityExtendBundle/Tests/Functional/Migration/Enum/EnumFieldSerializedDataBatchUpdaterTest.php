<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Migration\Enum;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Migration\Enum\EnumFieldSerializedDataBatchUpdater;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class EnumFieldSerializedDataBatchUpdaterTest extends WebTestCase
{
    private const string TABLE_NAME = 'oro_test_enum_batch_updater';
    private const string STATUS_ENUM_CODE = 'test_batch_status';
    private const string TAGS_ENUM_CODE = 'test_batch_tags';

    private Connection $connection;

    private EnumFieldSerializedDataBatchUpdater $updater;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->connection = self::getContainer()->get('doctrine')->getConnection();

        $this->updater = new EnumFieldSerializedDataBatchUpdater($this->connection);
        $this->createTestTable();
        $this->insertEnumOptions();
    }

    #[\Override]
    protected function tearDown(): void
    {
        if (!isset($this->connection)) {
            return;
        }

        $this->connection->executeStatement(sprintf('DROP TABLE IF EXISTS %s', self::TABLE_NAME));
        $this->connection->executeStatement(
            'DELETE FROM oro_enum_option WHERE enum_code IN (:enumCodes)',
            ['enumCodes' => [self::STATUS_ENUM_CODE, self::TAGS_ENUM_CODE]],
            ['enumCodes' => Connection::PARAM_STR_ARRAY]
        );
    }

    public function testUpdateEnum(): void
    {
        $this->insertEntityRow(1, 'active', null, null);

        self::assertNull($this->fetchSerializedData(1));

        $this->updater->updateEnum(
            self::TABLE_NAME,
            'id',
            'status_id',
            'status',
            self::STATUS_ENUM_CODE,
        );

        self::assertSame(
            ['status' => ExtendHelper::buildEnumOptionId(self::STATUS_ENUM_CODE, 'active')],
            $this->fetchSerializedData(1)
        );
    }

    public function testUpdateEnumPreservesExistingSerializedData(): void
    {
        $before = ['foo' => 'bar'];
        $this->insertEntityRow(1, 'pending', null, $before);

        self::assertSame($before, $this->fetchSerializedData(1));

        $this->updater->updateEnum(
            self::TABLE_NAME,
            'id',
            'status_id',
            'status',
            self::STATUS_ENUM_CODE,
        );

        self::assertSame(
            [
                'foo' => 'bar',
                'status' => ExtendHelper::buildEnumOptionId(self::STATUS_ENUM_CODE, 'pending'),
            ],
            $this->fetchSerializedData(1)
        );
    }

    public function testUpdateMultiEnum(): void
    {
        $this->insertEntityRow(1, null, 'beta,alpha', null);

        self::assertNull($this->fetchSerializedData(1));

        $this->updater->updateMultiEnum(
            self::TABLE_NAME,
            'id',
            'tags_ss',
            'tags',
            self::TAGS_ENUM_CODE,
        );

        self::assertSame(
            [
                'tags' => [
                    ExtendHelper::buildEnumOptionId(self::TAGS_ENUM_CODE, 'beta'),
                    ExtendHelper::buildEnumOptionId(self::TAGS_ENUM_CODE, 'alpha'),
                ],
            ],
            $this->fetchSerializedData(1)
        );
    }

    public function testUpdateMultiEnumSkipsRowWithUnmappedSnapshotValues(): void
    {
        $this->insertEntityRow(1, null, 'unknown', null);

        self::assertNull($this->fetchSerializedData(1));

        $this->updater->updateMultiEnum(
            self::TABLE_NAME,
            'id',
            'tags_ss',
            'tags',
            self::TAGS_ENUM_CODE,
        );

        self::assertNull($this->fetchSerializedData(1));
    }

    public function testUpdateEnumRespectsIdBatchRange(): void
    {
        $this->insertEntityRow(1, 'active', null, null);
        $this->insertEntityRow(2, 'pending', null, null);

        self::assertNull($this->fetchSerializedData(1));
        self::assertNull($this->fetchSerializedData(2));

        $this->updater->updateEnum(
            self::TABLE_NAME,
            'id',
            'status_id',
            'status',
            self::STATUS_ENUM_CODE,
            1,
            1,
        );

        self::assertSame(
            ['status' => ExtendHelper::buildEnumOptionId(self::STATUS_ENUM_CODE, 'active')],
            $this->fetchSerializedData(1)
        );
        self::assertNull($this->fetchSerializedData(2));
    }

    private function createTestTable(): void
    {
        $this->connection->executeStatement(sprintf(
            'CREATE TABLE %s (
                id INT PRIMARY KEY,
                status_id VARCHAR(32) DEFAULT NULL,
                tags_ss VARCHAR(500) DEFAULT NULL,
                serialized_data JSONB DEFAULT NULL
            )',
            self::TABLE_NAME
        ));
    }

    private function insertEnumOptions(): void
    {
        $this->insertEnumOption(self::STATUS_ENUM_CODE, 'active', 'Active', 1);
        $this->insertEnumOption(self::STATUS_ENUM_CODE, 'pending', 'Pending', 2);
        $this->insertEnumOption(self::TAGS_ENUM_CODE, 'alpha', 'Alpha', 1);
        $this->insertEnumOption(self::TAGS_ENUM_CODE, 'beta', 'Beta', 2);
    }

    private function insertEnumOption(string $enumCode, string $internalId, string $name, int $priority): void
    {
        $this->connection->insert('oro_enum_option', [
            'id' => ExtendHelper::buildEnumOptionId($enumCode, $internalId),
            'internal_id' => $internalId,
            'enum_code' => $enumCode,
            'name' => $name,
            'priority' => $priority,
            'is_default' => false,
        ], [
            'id' => Types::STRING,
            'internal_id' => Types::STRING,
            'enum_code' => Types::STRING,
            'name' => Types::STRING,
            'priority' => Types::INTEGER,
            'is_default' => Types::BOOLEAN,
        ]);
    }

    private function insertEntityRow(
        int $id,
        ?string $statusId,
        ?string $tagsSnapshot,
        ?array $serializedData,
    ): void {
        $this->connection->insert(self::TABLE_NAME, [
            'id' => $id,
            'status_id' => $statusId,
            'tags_ss' => $tagsSnapshot,
            'serialized_data' => $serializedData,
        ], [
            'id' => Types::INTEGER,
            'status_id' => Types::STRING,
            'tags_ss' => Types::STRING,
            'serialized_data' => Types::JSON,
        ]);
    }

    private function fetchSerializedData(int $id): ?array
    {
        $serializedData = $this->connection->fetchOne(
            sprintf('SELECT serialized_data FROM %s WHERE id = :id', self::TABLE_NAME),
            ['id' => $id],
            ['id' => Types::INTEGER]
        );

        if (null === $serializedData) {
            return null;
        }

        if (is_string($serializedData)) {
            return json_decode($serializedData, true, 512, JSON_THROW_ON_ERROR);
        }

        return $serializedData;
    }
}
