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
    public function testPlatformNotMatch()
    {
        $manager = $this->getEntityManager(SqlitePlatform::class);

        $event = $this->createMock(LoadClassMetadataEventArgs::class);
        $event->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($manager);
        $event->expects($this->never())
            ->method('getClassMetadata');

        $listener = new PartialIndexListener('table', 'index');
        $listener->loadClassMetadata($event);
    }

    public function testPlatformMatch()
    {
        $manager = $this->getEntityManager(MySqlPlatform::class);

        $classMetadata = $this->createMock(ClassMetadataInfo::class);
        $classMetadata->expects($this->any())
            ->method('getTableName')
            ->willReturn('table');
        $classMetadata->table = [
            'indexes' => [
                'index' => [
                    'options' => [
                        'where' => 'some condition',
                        'option2' => 'some value'
                    ]
                ]
            ]
        ];

        $event = $this->createMock(LoadClassMetadataEventArgs::class);
        $event->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($manager);
        $event->expects($this->once())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $listener = new PartialIndexListener('table', 'index');
        $listener->loadClassMetadata($event);

        self::assertArrayNotHasKey('where', $classMetadata->table['indexes']['index']['options']);
        self::assertArrayHasKey('option2', $classMetadata->table['indexes']['index']['options']);
    }

    private function getEntityManager(string $platformClass): EntityManager
    {
        $connection = $this->createMock(Connection::class);
        $platform = $this->createMock($platformClass);
        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn($platform);
        $manager = $this->createMock(EntityManager::class);
        $manager->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);

        return $manager;
    }
}
