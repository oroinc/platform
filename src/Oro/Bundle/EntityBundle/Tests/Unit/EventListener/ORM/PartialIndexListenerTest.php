<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\EventListener\ORM\PartialIndexListener;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;

class PartialIndexListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LoadClassMetadataEventArgs|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $event;

    /**
     * @var ClassMetadataInfo|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $metadata;

    /**
     * @var PartialIndexListener
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

    public function testPlatformNotMatch()
    {
        $manager = $this->getManagerMock('db_driver');
        $this->event->method('getEntityManager')->willReturn($manager);
        $this->event
            ->expects($this->never())
            ->method('getClassMetadata');

        $listener = new PartialIndexListener('table', 'index');
        $listener->loadClassMetadata($this->event);
    }

    public function testPlatformMatch()
    {
        $manager = $this->getManagerMock(DatabaseDriverInterface::DRIVER_MYSQL);
        $this->event->method('getEntityManager')->willReturn($manager);

        $classMetadataInfo = $this->getClassMetadataInfoMock('table', 'index');
        $this->event
            ->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadataInfo);

        $listener = new PartialIndexListener('table', 'index');
        $listener->loadClassMetadata($this->event);
        static::assertArrayNotHasKey('where', $classMetadataInfo->table['indexes']['index']['options']);
        static::assertArrayHasKey('option2', $classMetadataInfo->table['indexes']['index']['options']);
    }

    /**
     * @param $driverName
     *
     * @return EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getManagerMock($driverName)
    {
        $connection = $this->createMock(Connection::class);
        $driver = $this->createMock(Driver::class);
        $driver->method('getName')->willReturn($driverName);
        $connection->method('getDriver')->willReturn($driver);
        $manager = $this->createMock(EntityManager::class);
        $manager->method('getConnection')->willReturn($connection);

        return $manager;
    }

    /**
     * @param string $table
     * @param string $index
     *
     * @return ClassMetadataInfo|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getClassMetadataInfoMock($table, $index)
    {
        $classInfo = $this->createMock(ClassMetadataInfo::class);
        $classInfo->method('getTableName')->willReturn($table);
        $classInfo->table = [
           'indexes' => [
               $index => [
                   'options' => [
                       'where' => 'some condition',
                       'option2' => 'some value'
                   ]
               ]
           ]
        ];

        return $classInfo;
    }
}
