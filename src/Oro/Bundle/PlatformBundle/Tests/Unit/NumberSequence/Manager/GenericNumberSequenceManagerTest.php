<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Unit\NumberSequence\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;
use Oro\Bundle\PlatformBundle\Entity\Repository\NumberSequenceRepository;
use Oro\Bundle\PlatformBundle\NumberSequence\Manager\GenericNumberSequenceManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GenericNumberSequenceManagerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $repository;
    private GenericNumberSequenceManager $manager;

    private string $sequenceType = 'invoice';
    private string $discriminatorType = 'organization_periodic';
    private string $discriminatorValue = '1:2024-01';

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(NumberSequenceRepository::class);

        $this->doctrine
            ->method('getManagerForClass')
            ->with(NumberSequence::class)
            ->willReturn($this->em);

        $this->em
            ->method('getRepository')
            ->with(NumberSequence::class)
            ->willReturn($this->repository);

        $this->manager = new GenericNumberSequenceManager(
            $this->doctrine,
            $this->sequenceType,
            $this->discriminatorType,
            $this->discriminatorValue
        );
    }

    public function testNextNumberCreatesNewSequence(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('getLockedSequence')
            ->willReturn(null);

        $this->em
            ->expects(self::once())
            ->method('persist')
            ->with($this->isInstanceOf(NumberSequence::class));
        $this->em
            ->expects(self::once())
            ->method('flush');
        $this->em
            ->expects(self::once())
            ->method('commit');
        $this->em
            ->expects(self::once())
            ->method('beginTransaction');
        $this->em
            ->expects(self::never())
            ->method('rollback');

        $next = $this->manager->nextNumber();
        self::assertEquals(1, $next);
    }

    public function testNextNumberIncrementsExisting(): void
    {
        $sequence = new NumberSequence();
        $sequence
            ->setSequenceType($this->sequenceType)
            ->setDiscriminatorType($this->discriminatorType)
            ->setDiscriminatorValue($this->discriminatorValue)
            ->setNumber(5);

        $this->repository
            ->expects(self::once())
            ->method('getLockedSequence')
            ->willReturn($sequence);

        $this->em
            ->expects(self::never())
            ->method('persist');
        $this->em
            ->expects(self::once())
            ->method('flush');
        $this->em
            ->expects(self::once())
            ->method('commit');
        $this->em
            ->expects(self::once())
            ->method('beginTransaction');

        $next = $this->manager->nextNumber();
        self::assertEquals(6, $next);
        self::assertEquals(6, $sequence->getNumber());
    }

    public function testResetSequence(): void
    {
        $sequence = new NumberSequence();
        $sequence
            ->setSequenceType($this->sequenceType)
            ->setDiscriminatorType($this->discriminatorType)
            ->setDiscriminatorValue($this->discriminatorValue)
            ->setNumber(5);

        $this->repository
            ->expects(self::once())
            ->method('getLockedSequence')
            ->willReturn($sequence);

        $this->em
            ->expects(self::once())
            ->method('flush');
        $this->em
            ->expects(self::once())
            ->method('commit');
        $this->em
            ->expects(self::once())
            ->method('beginTransaction');

        $this->manager->resetSequence(0);
        self::assertEquals(0, $sequence->getNumber());
    }

    public function testReserveSequence(): void
    {
        $sequence = new NumberSequence();
        $sequence
            ->setSequenceType($this->sequenceType)
            ->setDiscriminatorType($this->discriminatorType)
            ->setDiscriminatorValue($this->discriminatorValue)
            ->setNumber(10);

        $this->repository
            ->expects(self::once())
            ->method('getLockedSequence')
            ->willReturn($sequence);

        $this->em
            ->expects(self::once())
            ->method('flush');
        $this->em
            ->expects(self::once())
            ->method('commit');
        $this->em
            ->expects(self::once())
            ->method('beginTransaction');

        $range = $this->manager->reserveSequence(5);
        self::assertEquals([11, 12, 13, 14, 15], $range);
        self::assertEquals(15, $sequence->getNumber());
    }

    public function testReserveSequenceThrowsOnInvalidSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Size must be a positive integer.');

        $this->manager->reserveSequence(0);
    }
}
