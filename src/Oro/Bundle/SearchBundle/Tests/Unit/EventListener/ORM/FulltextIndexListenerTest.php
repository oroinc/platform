<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;
use Oro\Bundle\SearchBundle\Entity\IndexText;
use Oro\Bundle\SearchBundle\EventListener\ORM\FulltextIndexListener;

class FulltextIndexListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var LoadClassMetadataEventArgs|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ClassMetadataInfo */
    private $metadata;

    /** @var FulltextIndexListener */
    private $listener;

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
        $driver = $this->createMock(Driver::class);
        $driver->expects($this->any())
            ->method('getName')
            ->willReturn($databaseDriver);
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->any())
            ->method('getDriver')
            ->willReturn($driver);
        $connection->expects($this->any())
            ->method('fetchColumn')
            ->with('select version()')
            ->willReturn($returnMysqlVersion);

        $this->listener = new FulltextIndexListener($textIndexTableName, $connection);
    }

    public function testPlatformNotMatch()
    {
        $this->initListener('not_mysql', 'expectedTextIndexTableName');

        $this->event->expects($this->never())
            ->method('getClassMetadata');

        $this->listener->loadClassMetadata($this->event);
        $this->assertNull($this->metadata->table);
    }

    public function testTableNotMatch()
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
    public function testTableEngineDependsOnMysqlVersionOptions(string $mysqlVersion, string $tableEngine)
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
