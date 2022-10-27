<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\AttachmentBundle\Migration\SetAllowedMimeTypesForImageFieldQuery;
use Psr\Log\LoggerInterface;

class SetAllowedMimeTypesForImageFieldQueryTest extends \PHPUnit\Framework\TestCase
{
    private const CLASS_NAME = 'Test\Entity';
    private const FIELD_NAME = 'testField';
    private const MIME_TYPES = ['testType1', 'testType2'];

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var SetAllowedMimeTypesForImageFieldQuery */
    private $updateAttachmentOptionQuery;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->updateAttachmentOptionQuery = new SetAllowedMimeTypesForImageFieldQuery(
            self::CLASS_NAME,
            self::FIELD_NAME,
            self::MIME_TYPES
        );
    }

    public function testExecuteWithoutRowResult()
    {
        $this->connection->expects(self::once())
            ->method('fetchAssoc')
            ->with($this->getSelectFromConfigField(), [self::CLASS_NAME, self::FIELD_NAME])
            ->willReturn(null);
        $this->connection->expects(self::never())
            ->method('convertToPHPValue');
        $this->connection->expects(self::never())
            ->method('prepare');
        $this->updateAttachmentOptionQuery->setConnection($this->connection);
        $this->updateAttachmentOptionQuery->execute($this->logger);
    }

    public function testExecuteWithMimeTypes()
    {
        $this->connection->expects(self::once())
            ->method('fetchAssoc')
            ->with($this->getSelectFromConfigField(), [self::CLASS_NAME, self::FIELD_NAME])
            ->willReturn([
                'data' =>'data persisted serialized',
                'id' => 56
            ]);
        $this->connection->expects(self::once())
            ->method('convertToPHPValue')
            ->with('data persisted serialized', Types::ARRAY)
            ->willReturn([
                'attachment' => [
                    'mimetypes' => [
                        'someType'
                    ]
                ]
            ]);

        $this->connection->expects(self::never())
            ->method('convertToDatabaseValue');
        $this->connection->expects(self::never())
            ->method('prepare');
        $this->updateAttachmentOptionQuery->setConnection($this->connection);
        $this->updateAttachmentOptionQuery->getDescription();
    }

    public function testGetDescription()
    {
        $this->connection->expects(self::once())
            ->method('fetchAssoc')
            ->with($this->getSelectFromConfigField(), [self::CLASS_NAME, self::FIELD_NAME])
            ->willReturn([
                'data' => 'data persisted serialized',
                'id' => 16
            ]);
        $this->connection->expects(self::never())
            ->method('prepare');

        $this->connection->expects(self::once())
            ->method('convertToPHPValue')
            ->with('data persisted serialized', Types::ARRAY)
            ->willReturn([
                'attachment' => [
                    'width' => 100
                ]
            ]);

        $this->connection->expects(self::once())
            ->method('convertToDatabaseValue')
            ->with([
                'attachment' => [
                    'width' => 100,
                    'mimetypes' => 'testType1
testType2'
                ]
            ], Types::ARRAY)
            ->willReturn('data serialized to persist');
        $this->updateAttachmentOptionQuery->setConnection($this->connection);
        self::assertEquals(
            [
                $this->getSelectFromConfigField(),
                $this->getUpdateFromConfigField(),
                'Parameters:',
                '[1] = data serialized to persist',
                '[2] = 16'
            ],
            $this->updateAttachmentOptionQuery->getDescription()
        );
    }

    public function testExecute()
    {
        $this->connection->expects(self::once())
            ->method('fetchAssoc')
            ->with($this->getSelectFromConfigField(), [self::CLASS_NAME, self::FIELD_NAME])
            ->willReturn([
                'data' =>'data persisted serialized',
                'id' => 16
            ]);
        $this->connection->expects(self::once())
            ->method('convertToPHPValue')
            ->with('data persisted serialized', Types::ARRAY)
            ->willReturn([
                'attachment' => [
                    'width' => 100
                ]
            ]);

        $this->connection->expects(self::once())
            ->method('convertToDatabaseValue')
            ->with([
                'attachment' => [
                    'width' => 100,
                    'mimetypes' => 'testType1
testType2'
                ]
            ], Types::ARRAY)
            ->willReturn('data serialized to persist');
        $statement = $this->createMock(Statement::class);
        $this->connection->expects(self::once())
            ->method('prepare')
            ->with($this->getUpdateFromConfigField())
            ->willReturn($statement);
        $statement->expects(self::once())
            ->method('execute')
            ->with(['data serialized to persist', 16]);
        $this->updateAttachmentOptionQuery->setConnection($this->connection);
        $this->updateAttachmentOptionQuery->execute($this->logger);
    }

    private function getSelectFromConfigField(): string
    {
        return 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?';
    }

    private function getUpdateFromConfigField(): string
    {
        return 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';
    }
}
