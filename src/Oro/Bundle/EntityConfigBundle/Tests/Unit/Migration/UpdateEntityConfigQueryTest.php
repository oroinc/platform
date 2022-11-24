<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigQuery;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity1;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity2;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Psr\Log\LoggerInterface;

class UpdateEntityConfigQueryTest extends \PHPUnit\Framework\TestCase
{
    private const FIELD_NAME = 'fieldName';

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var UpdateEntityConfigQuery */
    private $query;

    /** @var string */
    private $relationFullName;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection->expects($this->once())
            ->method('fetchAssoc')
            ->with('SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1', [TestEntity1::class])
            ->willReturn(['id' => '42', 'data' => 'data persisted payload serialized']);

        $this->relationFullName = sprintf(
            '%s|%s|%s|%s',
            RelationType::MANY_TO_ONE,
            TestEntity1::class,
            TestEntity2::class,
            self::FIELD_NAME
        );
    }

    /**
     * @dataProvider getConfiguration
     */
    public function testGetDescription(string $key, mixed $value)
    {
        $this->initializeQuery($key, $value);
        $statement = $this->setUpConnection($key, $value);
        $statement->expects($this->never())
            ->method('execute');

        $this->query->setConnection($this->connection);

        $description = sprintf(
            'Update %s value to "%s" for meta field "%s" from entity "%s" to entity "%s" with relation "%s".',
            $key,
            var_export($value, true),
            self::FIELD_NAME,
            TestEntity1::class,
            TestEntity2::class,
            RelationType::MANY_TO_ONE
        );

        $this->assertEquals(
            [
                $description,
                'SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1',
                'Parameters:',
                '[1] = Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity1',
                'UPDATE oro_entity_config SET data = ? WHERE id = ?',
                'Parameters:',
                '[1] = data serialized payload to persist',
                '[2] = 42',
            ],
            $this->query->getDescription()
        );
    }

    /**
     * @dataProvider getConfiguration
     */
    public function testUpdateCascadeValue(string $key, mixed $value)
    {
        $this->initializeQuery($key, $value);
        $statement = $this->setUpConnection($key, $value);
        $statement->expects($this->once())
            ->method('execute')
            ->with(['data serialized payload to persist', '42']);

        $logger = new ArrayLogger;

        $this->query->setConnection($this->connection);
        $this->query->execute($logger);

        $this->assertEquals(
            [
                'SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1',
                'Parameters:',
                '[1] = Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity1',
                'UPDATE oro_entity_config SET data = ? WHERE id = ?',
                'Parameters:',
                '[1] = data serialized payload to persist',
                '[2] = 42',
            ],
            $logger->getMessages()
        );
    }

    /**
     * @dataProvider getConfiguration
     */
    public function testNoRelationAlert(string $key, mixed $value)
    {
        $this->initializeQuery($key, $value);
        $this->connection->expects($this->once())
            ->method('convertToPHPValue')
            ->with('data persisted payload serialized', Types::ARRAY)
            ->willReturn([
                'extend' => [
                    'relation' => [] //no relation defined in persisted entity config
                ]
            ]);

        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->exactly(3))
            ->method('info')
            ->withConsecutive(
                ['SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1'],
                ['Parameters:'],
                ['[1] = Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity1']
            );
        $logger->expects($this->once())
            ->method('warning')
            ->with(
                '{key} value for entity `{entity}` config field `{field}`' .
                ' was not updated as relation `{relation}` is not defined in configuration.',
                [
                    'key' => ucfirst($key),
                    'entity' => TestEntity1::class,
                    'field' => self::FIELD_NAME,
                    'relation' => $this->relationFullName
                ]
            );

        $this->query->setConnection($this->connection);
        $this->query->execute($logger);
    }

    private function setUpConnection(string $key, mixed $value): Statement|\PHPUnit\Framework\MockObject\MockObject
    {
        $this->connection->expects($this->once())
            ->method('convertToPHPValue')
            ->with('data persisted payload serialized', Types::ARRAY)
            ->willReturn(
                [
                    'extend' => [
                        'relation' => [
                            $this->relationFullName => [$key => $value]
                        ]
                    ]
                ]
            );

        $this->connection->expects($this->once())
            ->method('convertToDatabaseValue')
            ->with(
                [
                    'extend' => [
                        'relation' => [
                            $this->relationFullName => [$key => $value]
                        ]
                    ]
                ],
                Types::ARRAY
            )->willReturn('data serialized payload to persist');

        $statement = $this->createMock(Statement::class);

        $this->connection->expects($this->once())
            ->method('prepare')
            ->with('UPDATE oro_entity_config SET data = ? WHERE id = ?')
            ->willReturn($statement);

        return $statement;
    }

    public function getConfiguration(): array
    {
        return [
            [
                'on_delete',
                'CASCADE'
            ],
            [
                'cascade',
                ['ALL']
            ],
        ];
    }

    private function initializeQuery(string $key, mixed $value): void
    {
        $this->query = new UpdateEntityConfigQuery(
            TestEntity1::class,
            TestEntity2::class,
            RelationType::MANY_TO_ONE,
            self::FIELD_NAME,
            $key,
            $value
        );
    }
}
