<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Tools;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Result;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadataFactory;
use Oro\Bundle\EntityConfigBundle\Config\EntityManagerBag;
use Oro\Bundle\EntityConfigBundle\EntityConfig\ConfigurationHandler;
use Oro\Bundle\EntityConfigBundle\Metadata\EntityMetadata;
use Oro\Bundle\EntityConfigBundle\Metadata\Factory\MetadataFactory;
use Oro\Bundle\EntityConfigBundle\Metadata\FieldMetadata;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;

class ConfigLoaderTest extends TestCase
{
    private const int ENTITY_CONFIG_ID = 42;
    private const int FIELD_CONFIG_ID = 7;
    private const string FIELD_NAME = 'name';

    private const string INSERT_ENTITY_CONFIG_SQL = 'INSERT INTO oro_entity_config '
        . '(class_name, created, updated, mode, data) '
        . 'VALUES(?, ?, ?, ?, ?)';

    private Connection&MockObject $connection;
    private ConfigLoader $configLoader;

    /** @var array [[sql, params], ...] for every executeStatement() call */
    private array $executedStatements = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection->expects(self::any())
            ->method('executeQuery')
            ->willReturnCallback(function (string $sql): Result {
                $result = $this->createMock(Result::class);
                $result->expects(self::any())
                    ->method('fetchAllAssociative')
                    ->willReturn(
                        str_starts_with($sql, 'SELECT id, entity_id, field_name FROM oro_entity_config_field')
                            ? [[
                                'id' => self::FIELD_CONFIG_ID,
                                'entity_id' => self::ENTITY_CONFIG_ID,
                                'field_name' => self::FIELD_NAME
                            ]]
                            : []
                    );

                return $result;
            });
        $this->connection->expects(self::any())
            ->method('executeStatement')
            ->willReturnCallback(function (string $sql, array $params = []): int {
                $this->executedStatements[] = [$sql, $params];

                return 1;
            });

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getConnection')
            ->willReturn($this->connection);
        $em->expects(self::any())
            ->method('getMetadataFactory')
            ->willReturn($this->getOrmMetadataFactory());

        $entityManagerBag = $this->createMock(EntityManagerBag::class);
        $entityManagerBag->expects(self::any())
            ->method('getEntityManagers')
            ->willReturn([$em]);

        $entityMetadata = new EntityMetadata(DemoEntity::class);
        $entityMetadata->fieldMetadata[self::FIELD_NAME] = new FieldMetadata(DemoEntity::class, self::FIELD_NAME);
        $metadataFactory = $this->createMock(MetadataFactory::class);
        $metadataFactory->expects(self::any())
            ->method('getMetadataForClass')
            ->with(DemoEntity::class)
            ->willReturn($entityMetadata);

        $propertyConfig = $this->createMock(PropertyConfigContainer::class);
        $propertyConfig->expects(self::any())
            ->method('getIndexedValues')
            ->willReturn([]);
        $provider = $this->createMock(ConfigProvider::class);
        $provider->expects(self::any())
            ->method('getPropertyConfig')
            ->willReturn($propertyConfig);
        $providerBag = $this->createMock(ConfigProviderBag::class);
        $providerBag->expects(self::any())
            ->method('getProviders')
            ->willReturn([]);
        $providerBag->expects(self::any())
            ->method('getProvider')
            ->willReturn($provider);

        $this->configLoader = new ConfigLoader(
            $entityManagerBag,
            $metadataFactory,
            $this->createMock(ConfigurationHandler::class),
            $providerBag,
            $this->createMock(CacheItemPoolInterface::class)
        );
    }

    public function testLoadInsertsEntityConfigOnPostgreSql(): void
    {
        $this->connection->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn(new PostgreSQLPlatform());
        $this->connection->expects(self::once())
            ->method('fetchOne')
            ->with(
                self::INSERT_ENTITY_CONFIG_SQL . ' RETURNING id',
                self::callback(fn (array $params): bool => DemoEntity::class === $params[0])
            )
            ->willReturn((string)self::ENTITY_CONFIG_ID);
        $this->connection->expects(self::never())
            ->method('lastInsertId');

        $this->configLoader->load();

        self::assertNotContains(self::INSERT_ENTITY_CONFIG_SQL, array_column($this->executedStatements, 0));
        $this->assertFieldConfigInsertedFor(self::ENTITY_CONFIG_ID);
    }

    public function testLoadInsertsEntityConfigOnMySql(): void
    {
        $this->connection->expects(self::any())
            ->method('getDatabasePlatform')
            ->willReturn(new MySQLPlatform());
        $this->connection->expects(self::never())
            ->method('fetchOne');
        $this->connection->expects(self::once())
            ->method('lastInsertId')
            ->with(null)
            ->willReturn((string)self::ENTITY_CONFIG_ID);

        $this->configLoader->load();

        self::assertContains(self::INSERT_ENTITY_CONFIG_SQL, array_column($this->executedStatements, 0));
        $this->assertFieldConfigInsertedFor(self::ENTITY_CONFIG_ID);
    }

    private function assertFieldConfigInsertedFor(int $entityConfigId): void
    {
        $fieldInserts = array_values(array_filter(
            $this->executedStatements,
            fn (array $statement): bool => str_starts_with($statement[0], 'INSERT INTO oro_entity_config_field ')
        ));

        self::assertCount(1, $fieldInserts);
        self::assertSame($entityConfigId, $fieldInserts[0][1][0]);
        self::assertSame(self::FIELD_NAME, $fieldInserts[0][1][1]);
    }

    private function getOrmMetadataFactory(): ClassMetadataFactory&MockObject
    {
        $ormMetadata = $this->createMock(ClassMetadata::class);
        $ormMetadata->expects(self::any())
            ->method('getName')
            ->willReturn(DemoEntity::class);
        $ormMetadata->expects(self::any())
            ->method('getIdentifierColumnNames')
            ->willReturn(['id']);
        $ormMetadata->expects(self::any())
            ->method('getFieldNames')
            ->willReturn(['id', self::FIELD_NAME]);
        $ormMetadata->expects(self::any())
            ->method('getAssociationNames')
            ->willReturn([]);
        $ormMetadata->expects(self::any())
            ->method('getTypeOfField')
            ->willReturnMap([['id', 'integer'], [self::FIELD_NAME, 'string']]);

        $ormMetadataFactory = $this->createMock(ClassMetadataFactory::class);
        $ormMetadataFactory->expects(self::any())
            ->method('getAllMetadata')
            ->willReturn([$ormMetadata]);

        return $ormMetadataFactory;
    }
}
