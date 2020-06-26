<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\EventListener\ORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\EntityBundle\EventListener\ORM\PartialIndexListener;

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

    protected function setUp(): void
    {
        $this->event = $this->createMock(LoadClassMetadataEventArgs::class);
        $this->metadata = $this->createMock(ClassMetadataInfo::class);
    }

    public function testPlatformNotMatch()
    {
        $manager = $this->getManagerMock(SqlitePlatform::class);
        $this->event->method('getEntityManager')->willReturn($manager);
        $this->event
            ->expects($this->never())
            ->method('getClassMetadata');

        $listener = new PartialIndexListener('table', 'index');
        $listener->loadClassMetadata($this->event);
    }

    public function testPlatformMatch()
    {
        $manager = $this->getManagerMock(MySqlPlatform::class);
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
     * @param string $platformClass
     *
     * @return EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getManagerMock($platformClass)
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createMock($platformClass);
        $connection->method('getDatabasePlatform')->willReturn($platform);
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
