<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigCascadeQuery;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity1;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\Stub\TestEntity2;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Psr\Log\LoggerInterface;

class UpdateEntityConfigCascadeQueryTest extends \PHPUnit_Framework_TestCase
{
    const FIELD_NAME = 'fieldName';
    const CASCADE_TYPES_NEW_VALUE = ['all'];

    /** @var Connection|\PHPUnit_Framework_MockObject_MockObject */
    private $connection;

    /** @var UpdateEntityConfigCascadeQuery */
    private $query;

    /** @var string */
    private $relationFullName;

    protected function setUp()
    {
        $this->connection = $this->createMock(Connection::class);
        $this->connection->expects($this->once())
            ->method('fetchAssoc')
            ->with('SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1', [TestEntity1::class])
            ->willReturn(['id' => '42', 'data' => 'data persisted payload serialized']);

        $this->query = new UpdateEntityConfigCascadeQuery(
            TestEntity1::class,
            TestEntity2::class,
            RelationType::MANY_TO_ONE,
            self::FIELD_NAME,
            self::CASCADE_TYPES_NEW_VALUE
        );

        $this->relationFullName = sprintf(
            '%s|%s|%s|%s',
            RelationType::MANY_TO_ONE,
            TestEntity1::class,
            TestEntity2::class,
            self::FIELD_NAME
        );
    }

    public function testGetDescription()
    {
        $statement = $this->setUpConnection();
        $statement->expects($this->never())->method('execute');

        $this->query->setConnection($this->connection);

        $description = sprintf(
            'Update cascade value to "%s" for meta field "%s" from entity "%s" to entity "%s" with relation "%s".',
            var_export(self::CASCADE_TYPES_NEW_VALUE, true),
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

    public function testUpdateCascadeValue()
    {
        $statement = $this->setUpConnection();
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

    public function testNoRelationAlert()
    {
        $this->connection->expects($this->at(1))
            ->method('convertToPHPValue')
            ->with('data persisted payload serialized', Type::TARRAY)
            ->willReturn([
                'extend' => [
                    'relation' => [] //no relation defined in persisted entity config
                ]
            ]);

        /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger */
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
                'Cascade value for entity `{entity}` config field `{field}`' .
                ' was not updated as relation `{relation}` is not defined in configuration.',
                [
                    'entity' => TestEntity1::class,
                    'field' => self::FIELD_NAME,
                    'relation' => $this->relationFullName
                ]
            );

        $this->query->setConnection($this->connection);
        $this->query->execute($logger);
    }

    /**
     * @return Statement|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function setUpConnection()
    {
        $this->connection->expects($this->at(1))
            ->method('convertToPHPValue')
            ->with('data persisted payload serialized', Type::TARRAY)
            ->willReturn(
                [
                    'extend' => [
                        'relation' => [
                            $this->relationFullName => [
                                'cascade' => ['old value']
                            ]
                        ]
                    ]
                ]
            );

        $this->connection->expects($this->at(2))->method('convertToDatabaseValue')
            ->with(
                [
                    'extend' => [
                        'relation' => [
                            $this->relationFullName => [
                                'cascade' => self::CASCADE_TYPES_NEW_VALUE
                            ]
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
}
