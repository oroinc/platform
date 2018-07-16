<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;
use Oro\Bundle\SearchBundle\Entity\IndexText;
use Oro\Bundle\SearchBundle\EventListener\ORM\FulltextIndexListener;

class FulltextIndexListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LoadClassMetadataEventArgs|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $event;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ClassMetadataInfo
     */
    protected $metadata;

    /**
     * @var FulltextIndexListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->event = $this
            ->getMockBuilder('Doctrine\ORM\Event\LoadClassMetadataEventArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $this->metadata = $this
            ->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataInfo')
            ->setMethods(['getTable', 'getTableName'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string $databaseDriver
     * @param string $textIndexTableName
     * @param string $returnMysqlVersion
     */
    protected function initListener($databaseDriver, $textIndexTableName, $returnMysqlVersion = '5.5')
    {
        $connection = $this
            ->getMockBuilder('Doctrine\DBAL\Connection')
            ->setMethods(['getDriver', 'getName', 'fetchColumn'])
            ->disableOriginalConstructor()
            ->getMock();
        $connection
            ->expects($this->any())
            ->method('getDriver')
            ->willReturn($connection);
        $connection
            ->expects($this->any())
            ->method('getName')
            ->willReturn($databaseDriver);

        $connection
            ->expects($this->any())
            ->method('fetchColumn')
            ->with('select version()')
            ->willReturn($returnMysqlVersion);

        $this->listener = new FulltextIndexListener($textIndexTableName, $connection);
    }

    public function testPlatformNotMatch()
    {
        $this->initListener('not_mysql', 'expectedTextIndexTableName');

        $this->event
            ->expects($this->never())
            ->method('getClassMetadata');

        $this->metadata
            ->expects($this->never())
            ->method('getTable');

        $this->listener->loadClassMetadata($this->event);
        $this->assertNull($this->metadata->table);
    }

    public function testTableNotMatch()
    {
        $this->initListener(DatabaseDriverInterface::DRIVER_MYSQL, 'expectedTextIndexTableName');

        $this->metadata
            ->expects($this->once())
            ->method('getTableName')
            ->will($this->returnValue('notExpectedTextIndexTableName'));

        $this->event
            ->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($this->metadata));

        $this->listener->loadClassMetadata($this->event);
        $this->assertNull($this->metadata->table);
    }

    /**
     * @dataProvider getMysqlVersionsProvider
     *
     * @param string $mysqlVersion
     */
    public function testTableEngineDependsOnMysqlVersionOptions($mysqlVersion, $tableEngine)
    {
        $this->initListener(DatabaseDriverInterface::DRIVER_MYSQL, IndexText::TABLE_NAME, $mysqlVersion);

        $this->metadata
            ->expects($this->once())
            ->method('getTableName')
            ->will($this->returnValue(IndexText::TABLE_NAME));

        $this->event
            ->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($this->metadata));

        $this->listener->loadClassMetadata($this->event);

        $this->assertEquals(
            [
                'options' => ['engine' => $tableEngine],
                'indexes' => ['value' => ['columns' => ['value'], 'flags' => ['fulltext']]],
            ],
            $this->metadata->table
        );
    }

    /**
     * @return array
     */
    public function getMysqlVersionsProvider()
    {
        return [
            ['5.5.5', PdoMysql::ENGINE_MYISAM],
            ['5.6.6', PdoMysql::ENGINE_INNODB]
        ];
    }
}
