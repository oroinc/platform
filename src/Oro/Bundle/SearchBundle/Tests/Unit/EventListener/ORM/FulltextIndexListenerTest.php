<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;
use Oro\Bundle\SearchBundle\Entity\IndexText;
use Oro\Bundle\SearchBundle\EventListener\ORM\FulltextIndexListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FulltextIndexListenerTest extends TestCase
{
    private LoadClassMetadataEventArgs&MockObject $event;
    private ClassMetadataInfo&MockObject $metadata;
    private FulltextIndexListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->event = $this->createMock(LoadClassMetadataEventArgs::class);
        $this->metadata = $this->createMock(ClassMetadataInfo::class);
    }

    private function initListener(
        string $databaseDriver,
        string $textIndexTableName,
        string $returnMysqlVersion = '5.5'
    ): void {
        $connection = $this->createMock(Connection::class);
        $abstractPlatform = $this->createMock(AbstractPlatform::class);
        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($abstractPlatform);

        $abstractPlatform->expects($this->any())
            ->method('getName')
            ->willReturn($databaseDriver);

        $connection->expects($this->any())
            ->method('fetchOne')
            ->with('select version()')
            ->willReturn($returnMysqlVersion);

        $this->listener = new FulltextIndexListener($textIndexTableName, $connection);
    }

    public function testPlatformNotMatch(): void
    {
        $this->initListener('not_mysql', 'expectedTextIndexTableName');

        $this->event->expects($this->never())
            ->method('getClassMetadata');

        $this->listener->loadClassMetadata($this->event);
        $this->assertNull($this->metadata->table);
    }

    public function testTableNotMatch(): void
    {
        $this->initListener(DatabaseDriverInterface::DRIVER_MYSQL, 'expectedTextIndexTableName');

        $this->metadata->expects($this->once())
            ->method('getTableName')
            ->willReturn('notExpectedTextIndexTableName');

        $this->event->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($this->metadata);

        $this->listener->loadClassMetadata($this->event);
        $this->assertNull($this->metadata->table);
    }

    /**
     * @dataProvider getMysqlVersionsProvider
     */
    public function testTableEngineDependsOnMysqlVersionOptions(string $mysqlVersion, string $tableEngine): void
    {
        $this->initListener(DatabaseDriverInterface::DRIVER_MYSQL, IndexText::TABLE_NAME, $mysqlVersion);

        $this->metadata->expects($this->once())
            ->method('getTableName')
            ->willReturn(IndexText::TABLE_NAME);

        $this->event->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($this->metadata);

        $this->listener->loadClassMetadata($this->event);

        $this->assertEquals(
            [
                'options' => ['engine' => $tableEngine],
                'indexes' => ['value' => ['columns' => ['value'], 'flags' => ['fulltext']]],
            ],
            $this->metadata->table
        );
    }

    public function getMysqlVersionsProvider(): array
    {
        return [
            ['5.5.5', PdoMysql::ENGINE_MYISAM],
            ['5.6.6', PdoMysql::ENGINE_INNODB]
        ];
    }
}
