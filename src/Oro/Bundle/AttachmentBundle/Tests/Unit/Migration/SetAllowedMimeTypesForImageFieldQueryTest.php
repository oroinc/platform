<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\AttachmentBundle\Migration\SetAllowedMimeTypesForImageFieldQuery;
use Psr\Log\LoggerInterface;

class SetAllowedMimeTypesForImageFieldQueryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    private $className = 'Oro\Bundle\CatalogBundle\Tests\Unit\Stub\TestEntity1';

    /**
     * @var string
     */
    private $fieldName = 'testField';

    /**
     * @var array
     */
    private $mimeTypes = [
        'testType1',
        'testType2'
    ];

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger
     */
    private $logger;

    /**
     * @var Connection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connection;

    /**
     * @var SetAllowedMimeTypesForImageFieldQuery
     */
    private $updateAttachmentOptionQuery;

    protected function setUp()
    {
        $this->connection = $this->createMock(Connection::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->updateAttachmentOptionQuery = new SetAllowedMimeTypesForImageFieldQuery(
            $this->className,
            $this->fieldName,
            $this->mimeTypes
        );
    }

    public function testExexuteWithoutRowResult()
    {
        $this->connection->expects(static::once())
            ->method('fetchAssoc')
            ->with($this->getSelectFromConfigField(), [$this->className, $this->fieldName])
            ->willReturn(null);
        $this->connection->expects(static::never())
            ->method('convertToPHPValue');
        $this->connection->expects(static::never())
            ->method('prepare');
        $this->updateAttachmentOptionQuery->setConnection($this->connection);
        $this->updateAttachmentOptionQuery->execute($this->logger);
    }

    public function testExexuteWithMimeTypes()
    {
        $this->connection->expects(static::once())
            ->method('fetchAssoc')
            ->with($this->getSelectFromConfigField(), [$this->className, $this->fieldName])
            ->willReturn([
                'data' =>'data persisted serialized',
                'id' => 56
            ]);
        $this->connection->expects(static::once())
            ->method('convertToPHPValue')
            ->with('data persisted serialized', Type::TARRAY)
            ->willReturn([
                'attachment' => [
                    'mimetypes' => [
                        'someType'
                    ]
                ]
            ]);

        $this->connection->expects(static::never())
            ->method('convertToDatabaseValue');
        $this->connection->expects(static::never())
            ->method('prepare');
        $this->updateAttachmentOptionQuery->setConnection($this->connection);
        $this->updateAttachmentOptionQuery->getDescription();
    }

    public function testGetDescription()
    {
        $this->connection->expects(static::once())
            ->method('fetchAssoc')
            ->with($this->getSelectFromConfigField(), [$this->className, $this->fieldName])
            ->willReturn([
                'data' => 'data persisted serialized',
                'id' => 16
            ]);
        $this->connection->expects(static::never())
            ->method('prepare');

        $this->connection->expects(static::once())
            ->method('convertToPHPValue')
            ->with('data persisted serialized', Type::TARRAY)
            ->willReturn([
                'attachment' => [
                    'width' => 100
                ]
            ]);

        $this->connection->expects(static::once())
            ->method('convertToDatabaseValue')
            ->with([
                'attachment' => [
                    'width' => 100,
                    'mimetypes' => 'testType1
testType2'
                ]
            ], Type::TARRAY)
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
        $this->connection->expects(static::once())
            ->method('fetchAssoc')
            ->with($this->getSelectFromConfigField(), [$this->className, $this->fieldName])
            ->willReturn([
                'data' =>'data persisted serialized',
                'id' => 16
            ]);
        $this->connection->expects(static::once())
            ->method('convertToPHPValue')
            ->with('data persisted serialized', Type::TARRAY)
            ->willReturn([
                'attachment' => [
                    'width' => 100
                ]
            ]);

        $this->connection->expects(static::once())
            ->method('convertToDatabaseValue')
            ->with([
                'attachment' => [
                    'width' => 100,
                    'mimetypes' => 'testType1
testType2'
                ]
            ], Type::TARRAY)
            ->willReturn('data serialized to persist');
        $statement = $this->createMock(Statement::class);
        $this->connection->expects(static::once())
            ->method('prepare')
            ->with($this->getUpdateFromConfigField())
            ->willReturn($statement);
        $statement->expects(static::once())
            ->method('execute')
            ->with(['data serialized to persist', 16]);
        $this->updateAttachmentOptionQuery->setConnection($this->connection);
        $this->updateAttachmentOptionQuery->execute($this->logger);
    }

    /**
     * @return string
     */
    private function getSelectFromConfigField()
    {
        return 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?';
    }

    /**
     * @return string
     */
    private function getUpdateFromConfigField()
    {
        return 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';
    }
}
