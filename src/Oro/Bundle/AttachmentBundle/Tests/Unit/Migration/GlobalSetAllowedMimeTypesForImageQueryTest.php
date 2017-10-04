<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Statement;
use Oro\Bundle\AttachmentBundle\Migration\GlobalSetAllowedMimeTypesForImageQuery;
use Psr\Log\LoggerInterface;

class GlobalSetAllowedMimeTypesForImageQueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var array
     */
    private $mimeTypes = [
        'testType1',
        'testType2',
    ];

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $logger
     */
    private $logger;

    /**
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connection;

    /**
     * @var GlobalSetAllowedMimeTypesForImageQuery
     */
    private $updateAttachmentOptionQuery;

    protected function setUp()
    {
        $this->connection = $this->createMock(Connection::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->updateAttachmentOptionQuery = new GlobalSetAllowedMimeTypesForImageQuery($this->mimeTypes);
    }

    public function testExecuteWithoutRowResult()
    {
        $this->connection->expects(static::once())
            ->method('fetchAssoc')
            ->with($this->getSelectQuery(), ['upload_image_mime_types'])
            ->willReturn(null);
        $this->connection->expects(static::never())
            ->method('prepare');
        $this->updateAttachmentOptionQuery->setConnection($this->connection);
        $this->updateAttachmentOptionQuery->execute($this->logger);
    }

    public function testExecuteWithExistingMimeTypes()
    {
        $this->connection->expects(static::once())
            ->method('fetchAssoc')
            ->with($this->getSelectQuery(), ['upload_image_mime_types'])
            ->willReturn(
                [
                    'text_value' => "image/jpeg",
                    'id' => 56,
                ]
            );
        $statement = $this->createMock(Statement::class);
        $this->connection->expects(static::once())
            ->method('prepare')
            ->with($this->getUpdateQuery())
            ->willReturn($statement);
        $statement->expects(static::once())
            ->method('execute')
            ->with(['text_value' => "testType1\r\ntestType2\r\nimage/jpeg", 'id' => 56]);
        $this->updateAttachmentOptionQuery->setConnection($this->connection);
        $this->updateAttachmentOptionQuery->execute($this->logger);
    }

    public function testExecuteWithoutExistingMimeTypes()
    {
        $this->connection->expects(static::once())
            ->method('fetchAssoc')
            ->with($this->getSelectQuery(), ['upload_image_mime_types'])
            ->willReturn(
                [
                    'text_value' => "",
                    'id' => 56,
                ]
            );
        $statement = $this->createMock(Statement::class);
        $this->connection->expects(static::once())
            ->method('prepare')
            ->with($this->getUpdateQuery())
            ->willReturn($statement);
        $statement->expects(static::once())
            ->method('execute')
            ->with(['text_value' => "testType1\r\ntestType2", 'id' => 56]);
        $this->updateAttachmentOptionQuery->setConnection($this->connection);
        $this->updateAttachmentOptionQuery->execute($this->logger);
    }

    /**
     * @return string
     */
    protected function getSelectQuery()
    {
        return 'SELECT c.id, c.text_value FROM oro_config_value as c WHERE c.name = ?';
    }

    /**
     * @return string
     */
    protected function getUpdateQuery()
    {
        return 'UPDATE oro_config_value SET text_value = :text_value WHERE id = :id';
    }
}
