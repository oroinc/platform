<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\EventListener\ORM;

use Doctrine\ORM\Mapping\ClassMetadataInfo;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql;
use Oro\Bundle\SearchBundle\Entity\IndexText;
use Oro\Bundle\SearchBundle\EventListener\ORM\FulltextIndexListener;

class FulltextIndexListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
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

        $this->listener = new FulltextIndexListener(DatabaseDriverInterface::DRIVER_MYSQL);
    }

    public function testPlatformNotMatch()
    {
        $listener = new FulltextIndexListener('not_mysql');

        $this->event
            ->expects($this->never())
            ->method('getClassMetadata');

        $listener->loadClassMetadata($this->event);
    }

    public function testTableNotMatch()
    {
        $this->metadata
            ->expects($this->once())
            ->method('getTableName')
            ->will($this->returnValue('not_search'));

        $this->event
            ->expects($this->once())
            ->method('getClassMetadata')
            ->will($this->returnValue($this->metadata));

        $this->listener->loadClassMetadata($this->event);
    }

    public function testAddedOptions()
    {
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
