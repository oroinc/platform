<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;
use Oro\Bundle\SearchBundle\Entity\IndexText;
use Oro\Bundle\SearchBundle\EventListener\ORM\FulltextIndexListener;

class FulltextIndexListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoadClassMetadataEventArgs|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ClassMetadataInfo
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
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @param string $databaseDriver
     * @param string $textIndexTableName
     */
    protected function initListener($databaseDriver, $textIndexTableName)
    {
        $this->listener = new FulltextIndexListener($databaseDriver, $textIndexTableName);
    }

    public function testPlatformNotMatch()
    {
        $this->initListener('not_mysql', 'some_table_name');

        $this->event
            ->expects($this->never())
            ->method('getClassMetadata');

        $this->event
            ->expects($this->never())
            ->method('getClassMetadata');

        $this->listener->loadClassMetadata($this->event);
        $this->assertNull($this->metadata->table);
    }

    public function testTableNotMatch()
    {
        $this->initListener(DatabaseDriverInterface::DRIVER_MYSQL, IndexText::TABLE_NAME);

        $this->metadata
            ->expects($this->once())
            ->method('getTableName')
            ->will($this->returnValue('oro_website_search_index_text'));

        $this->event
            ->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($this->metadata));

        $this->listener->loadClassMetadata($this->event);
        $this->assertNull($this->metadata->table);
    }

    public function testBothPlatformAndTableNotMatch()
    {
        $this->initListener('not_matching_platform', 'not_matching_table');

        $this->metadata
            ->expects($this->never())
            ->method('getTableName');

        $this->event
            ->expects($this->never())
            ->method('getClassMetadata');

        $this->listener->loadClassMetadata($this->event);
        $this->assertNull($this->metadata->table);
    }

    public function testAddedOptions()
    {
        $this->initListener(DatabaseDriverInterface::DRIVER_MYSQL, IndexText::TABLE_NAME);

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
                'options' => ['engine' => PdoMysql::ENGINE_MYISAM],
                'indexes' => ['value' => ['columns' => ['value'], 'flags' => ['fulltext']]],
            ],
            $this->metadata->table
        );
    }
}
