<?php
declare(strict_types=1);

namespace Oro\Component\DoctrineUtils\Tests\Unit\ORM;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Oro\Component\DoctrineUtils\ORM\ArrayKeyTrueHydrator;
use PHPUnit\Framework\TestCase;

class ArrayKeyTrueHydratorTest extends TestCase
{
    public function testHydrateAllData(): void
    {
        $stmt = $this->createMock(Statement::class);
        $stmt->method('fetch')->willReturnOnConsecutiveCalls('one', 'two', 'three', false);
        $rsm = $this->createMock(ResultSetMapping::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($this->createMock(Connection::class));
        $entityManager->method('getEventManager')->willReturn($this->createMock(EventManager::class));

        $hydrator = new ArrayKeyTrueHydrator($entityManager);

        static::assertSame(['one' => true, 'two' => true, 'three' => true], $hydrator->hydrateAll($stmt, $rsm));
    }
}
