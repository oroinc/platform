<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Migration\RemoveEnumFieldQuery;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RemoveEnumFieldQueryTest extends TestCase
{
    private const string TEST_ENTITY_CLASS = 'TestEntity';
    private const string TEST_FIELD_NAME = 'testField';
    private const string TEST_ENUM_CODE = 'test_enum_code';
    private const string TEST_TABLE_NAME = 'test_entity_table';
    private const int FIELD_ID = 777;

    private LoggerInterface|MockObject $logger;
    private Connection|MockObject $connection;
    private ContainerInterface|MockObject $container;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * Creates a configured RemoveEnumFieldQuery with connection and optional container.
     */
    private function createQuery(
        string $entityClass = self::TEST_ENTITY_CLASS,
        bool $withContainer = false
    ): RemoveEnumFieldQuery {
        $query = new RemoveEnumFieldQuery($entityClass, self::TEST_FIELD_NAME);
        $query->setConnection($this->connection);

        if ($withContainer) {
            $query->setContainer($this->container);
        }

        return $query;
    }

    /**
     * Configures connection mock to return field data.
     */
    private function mockFieldData(array $fieldRow, ?array $fieldData = null): void
    {
        $this->connection->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn($fieldRow);

        if ($fieldData !== null) {
            $this->connection->expects(self::once())
                ->method('convertToPHPValue')
                ->with($fieldRow['data'], Types::ARRAY)
                ->willReturn($fieldData);
        }
    }

    /**
     * Configures doctrine manager registry mock.
     */
    private function mockDoctrineRegistry(string $entityClass, string $tableName): void
    {
        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects(self::once())
            ->method('getTableName')
            ->willReturn($tableName);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::once())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects(self::once())
            ->method('getManagerForClass')
            ->with($entityClass)
            ->willReturn($entityManager);

        $this->container->expects(self::once())
            ->method('get')
            ->with('doctrine')
            ->willReturn($managerRegistry);
    }

    /**
     * Creates a mock statement that expects executeQuery with given parameters.
     */
    private function createStatementMock(array $expectedParams): Statement|MockObject
    {
        $statement = $this->createMock(Statement::class);
        $statement->expects(self::once())
            ->method('executeQuery')
            ->with($expectedParams);

        return $statement;
    }

    public function testGetDescription(): void
    {
        $query = new RemoveEnumFieldQuery(self::TEST_ENTITY_CLASS, self::TEST_FIELD_NAME);

        self::assertEquals('Remove testField enum field data', $query->getDescription());
    }

    public function testExecuteWhenFieldNotFound(): void
    {
        $query = $this->createQuery();

        $this->connection->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn(false);

        $this->logger->expects(self::once())
            ->method('info')
            ->with("Enum field 'testField' from Entity 'TestEntity' is not found");

        $this->connection->expects(self::never())
            ->method('prepare');

        $query->execute($this->logger);
    }

    public function testExecuteWhenFieldIsNotEnumerable(): void
    {
        $query = $this->createQuery();

        $this->connection->expects(self::once())
            ->method('fetchAssociative')
            ->willReturn([
                'id' => self::FIELD_ID,
                'data' => 'serialized_data',
                'type' => 'string' // Not an enumerable type
            ]);

        $this->logger->expects(self::once())
            ->method('info')
            ->with("Field 'testField' from Entity 'TestEntity' is not Enumerable Type");

        $this->connection->expects(self::never())
            ->method('prepare');

        $query->execute($this->logger);
    }

    public function testExecuteWithEnumFieldWithoutEnumCode(): void
    {
        $query = $this->createQuery();

        $fieldData = [
            'extend' => ['some_config' => 'value']
        ];

        $this->mockFieldData(
            [
                'id' => self::FIELD_ID,
                'data' => 'serialized_field_data',
                'type' => 'enum'
            ],
            $fieldData
        );

        $statement = $this->createStatementMock([self::FIELD_ID]);

        $this->connection->expects(self::once())
            ->method('prepare')
            ->with('DELETE FROM oro_entity_config_field WHERE id = ?')
            ->willReturn($statement);

        $this->connection->expects(self::never())
            ->method('executeQuery');

        $query->execute($this->logger);
    }

    public function testExecuteWithEnumFieldAndEmptyEnumOptions(): void
    {
        $query = $this->createQuery(withContainer: true);

        $fieldData = [
            'enum' => ['enum_code' => self::TEST_ENUM_CODE]
        ];

        $this->mockDoctrineRegistry(self::TEST_ENTITY_CLASS, self::TEST_TABLE_NAME);
        $this->mockFieldData(
            [
                'id' => self::FIELD_ID,
                'data' => 'serialized_field_data',
                'type' => 'enum'
            ],
            $fieldData
        );

        $this->connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->with('SELECT id, name FROM oro_enum_option WHERE enum_code = ?', [self::TEST_ENUM_CODE])
            ->willReturn([]); // No enum options

        $statement1 = $this->createStatementMock([self::FIELD_ID]);
        $statement2 = $this->createStatementMock([self::TEST_FIELD_NAME, self::TEST_FIELD_NAME]);
        $statement3 = $this->createStatementMock([self::TEST_ENUM_CODE]);

        $this->connection->expects(self::exactly(3))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($statement1, $statement2, $statement3);

        $this->connection->expects(self::never())
            ->method('executeQuery');

        $query->execute($this->logger);
    }

    public function testExecuteWithEnumFieldAndEnumOptions(): void
    {
        $query = $this->createQuery(withContainer: true);

        $fieldData = [
            'enum' => ['enum_code' => self::TEST_ENUM_CODE]
        ];

        $enumOptions = [
            ['id' => 'option1', 'name' => 'Option 1'],
            ['id' => 'option2', 'name' => 'Option 2'],
        ];

        $this->mockDoctrineRegistry(self::TEST_ENTITY_CLASS, self::TEST_TABLE_NAME);
        $this->mockFieldData(
            [
                'id' => self::FIELD_ID,
                'data' => 'serialized_field_data',
                'type' => 'enum'
            ],
            $fieldData
        );

        // For fetchAllAssociative to get enum options
        $this->connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->with('SELECT id, name FROM oro_enum_option WHERE enum_code = ?', [self::TEST_ENUM_CODE])
            ->willReturn($enumOptions);

        // Mock executeQuery for deletion queries with array parameters
        $this->connection->expects(self::exactly(2))
            ->method('executeQuery')
            ->withConsecutive(
                [
                    'DELETE FROM oro_enum_option_trans WHERE foreign_key IN (?)',
                    [['option1', 'option2']],
                    [Connection::PARAM_STR_ARRAY]
                ],
                [
                    'DELETE FROM oro_translation_key WHERE key IN (?)',
                    [['oro.entity_extend.enum_option.option1', 'oro.entity_extend.enum_option.option2']],
                    [Connection::PARAM_STR_ARRAY]
                ]
            );

        $statement1 = $this->createStatementMock([self::FIELD_ID]);
        $statement2 = $this->createStatementMock([self::TEST_FIELD_NAME, self::TEST_FIELD_NAME]);
        $statement3 = $this->createStatementMock([self::TEST_ENUM_CODE]);

        $this->connection->expects(self::exactly(3))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($statement1, $statement2, $statement3);

        $query->execute($this->logger);
    }

    public function testExecuteWithMultiEnumFieldAndEnumOptions(): void
    {
        $query = $this->createQuery(withContainer: true);

        $fieldData = [
            'enum' => ['enum_code' => self::TEST_ENUM_CODE]
        ];

        $enumOptions = [
            ['id' => 'option1', 'name' => 'Option 1'],
            ['id' => 'option2', 'name' => 'Option 2'],
            ['id' => 'option3', 'name' => 'Option 3'],
        ];

        $this->mockDoctrineRegistry(self::TEST_ENTITY_CLASS, self::TEST_TABLE_NAME);
        $this->mockFieldData(
            [
                'id' => self::FIELD_ID,
                'data' => 'serialized_field_data',
                'type' => 'multiEnum'
            ],
            $fieldData
        );

        $this->connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->with('SELECT id, name FROM oro_enum_option WHERE enum_code = ?', [self::TEST_ENUM_CODE])
            ->willReturn($enumOptions);

        $this->connection->expects(self::exactly(2))
            ->method('executeQuery')
            ->withConsecutive(
                [
                    'DELETE FROM oro_enum_option_trans WHERE foreign_key IN (?)',
                    [['option1', 'option2', 'option3']],
                    [Connection::PARAM_STR_ARRAY]
                ],
                [
                    'DELETE FROM oro_translation_key WHERE key IN (?)',
                    [[
                        'oro.entity_extend.enum_option.option1',
                        'oro.entity_extend.enum_option.option2',
                        'oro.entity_extend.enum_option.option3',
                    ]],
                    [Connection::PARAM_STR_ARRAY]
                ]
            );

        $statement1 = $this->createStatementMock([self::FIELD_ID]);
        $statement2 = $this->createStatementMock([self::TEST_FIELD_NAME, self::TEST_FIELD_NAME]);
        $statement3 = $this->createStatementMock([self::TEST_ENUM_CODE]);

        $this->connection->expects(self::exactly(3))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($statement1, $statement2, $statement3);

        $query->execute($this->logger);
    }

    public function testExecuteWithEnumFieldWhenTableNameIsNull(): void
    {
        $query = $this->createQuery(\stdClass::class, withContainer: true);

        $fieldData = [
            'enum' => ['enum_code' => self::TEST_ENUM_CODE]
        ];

        $this->mockDoctrineRegistry(\stdClass::class, ''); // Empty string evaluates to false
        $this->mockFieldData(
            [
                'id' => self::FIELD_ID,
                'data' => 'serialized_field_data',
                'type' => 'enum'
            ],
            $fieldData
        );

        $this->connection->expects(self::once())
            ->method('fetchAllAssociative')
            ->with('SELECT id, name FROM oro_enum_option WHERE enum_code = ?', [self::TEST_ENUM_CODE])
            ->willReturn([]);

        $statement1 = $this->createStatementMock([self::FIELD_ID]);
        $statement2 = $this->createStatementMock([self::TEST_ENUM_CODE]);

        // Only 2 prepare calls: field deletion and enum option deletion (no UPDATE query)
        $this->connection->expects(self::exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls($statement1, $statement2);

        $query->execute($this->logger);
    }
}
