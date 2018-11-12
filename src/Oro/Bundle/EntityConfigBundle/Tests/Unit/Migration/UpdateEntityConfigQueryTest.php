<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigQuery;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity1;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity2;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Psr\Log\LoggerInterface;

class UpdateEntityConfigQueryTest extends \PHPUnit\Framework\TestCase
{
    const FIELD_NAME = 'fieldName';

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var UpdateEntityConfigQuery */
    private $query;

    /** @var string */
    private $relationFullName;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
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
     * @param $key
     * @param $value
     *
     * @dataProvider getConfiguration
     */
    public function testGetDescription($key, $value)
    {
        $this->initializeQuery($key, $value);
        $statement = $this->setUpConnection($key, $value);
        $statement->expects($this->never())->method('execute');

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
     * @param $key
     * @param $value
     *
     * @dataProvider getConfiguration
     */
    public function testUpdateCascadeValue($key, $value)
    {
        $this->initializeQuery($key, $value);
        $statement = $this->setUpConnection($key, $value);
        $statement->expects($this->once())->method('execute')->with(['data serialized payload to persist', '42']);

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
     * @param $key
     * @param $value
     *
     * @dataProvider getConfiguration
     */
    public function testNoRelationAlert($key, $value)
    {
        $this->initializeQuery($key, $value);
        $this->connection->expects($this->at(1))
            ->method('convertToPHPValue')
            ->with('data persisted payload serialized', Type::TARRAY)
            ->willReturn([
                'extend' => [
                    'relation' => [] //no relation defined in persisted entity config
                ]
            ]);

        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects($this->at(0))
            ->method('info')->with('SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1');
        $logger->expects($this->at(1))
            ->method('info')->with('Parameters:');
        $logger->expects($this->at(2))
            ->method('info')->with('[1] = Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity1');
        $logger->expects($this->at(3))
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

    /**
     * @param $key
     * @param $value
     * @return Statement|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function setUpConnection($key, $value)
    {
        $this->connection->expects($this->at(1))
            ->method('convertToPHPValue')
            ->with('data persisted payload serialized', Type::TARRAY)
            ->willReturn(
                [
                    'extend' => [
                        'relation' => [
                            $this->relationFullName => [$key => $value]
                        ]
                    ]
                ]
            );

        $this->connection->expects($this->at(2))->method('convertToDatabaseValue')
            ->with(
                [
                    'extend' => [
                        'relation' => [
                            $this->relationFullName => [$key => $value]
                        ]
                    ]
                ],
                Type::TARRAY
            )->willReturn('data serialized payload to persist');

        $statement = $this->createMock(Statement::class);

        $this->connection->expects($this->at(3))
            ->method('prepare')
            ->with('UPDATE oro_entity_config SET data = ? WHERE id = ?')
            ->willReturn($statement);

        return $statement;
    }

    /**
     * @return array
     */
    public function getConfiguration()
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

    /**
     * @param string $key
     * @param string $value
     */
    protected function initializeQuery($key, $value)
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
