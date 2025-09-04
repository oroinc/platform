<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Unit\NumberSequence\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;
use Oro\Bundle\PlatformBundle\Entity\Repository\NumberSequenceRepository;
use Oro\Bundle\PlatformBundle\NumberSequence\Manager\GenericNumberSequenceManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GenericNumberSequenceManagerTest extends TestCase
{
    private NumberSequenceRepository&MockObject $repository;
    private GenericNumberSequenceManager $manager;

    private string $sequenceType = 'invoice';
    private string $discriminatorType = 'organization_periodic';
    private string $discriminatorValue = '1:2024-01';

    protected function setUp(): void
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(NumberSequenceRepository::class);

        $doctrine
            ->method('getRepository')
            ->with(NumberSequence::class)
            ->willReturn($this->repository);

        $this->manager = new GenericNumberSequenceManager(
            $doctrine,
            $this->sequenceType,
            $this->discriminatorType,
            $this->discriminatorValue
        );
    }

    private function createNumberSequenceWithId(int $id, int $number): NumberSequence
    {
        $sequence = new NumberSequence();
        $sequence->setSequenceType($this->sequenceType);
        $sequence->setDiscriminatorType($this->discriminatorType);
        $sequence->setDiscriminatorValue($this->discriminatorValue);
        $sequence->setNumber($number);

        ReflectionUtil::setId($sequence, $id);

        return $sequence;
    }

    public function testNextNumber(): void
    {
        $sequence = $this->createNumberSequenceWithId(1, 5);

        $this->repository
            ->expects(self::once())
            ->method('incrementSequence')
            ->with(
                $this->sequenceType,
                $this->discriminatorType,
                $this->discriminatorValue,
                1
            )
            ->willReturn($sequence);

        $result = $this->manager->nextNumber();
        self::assertEquals(5, $result);
    }

    public function testNextNumberWithNewSequence(): void
    {
        $sequence = $this->createNumberSequenceWithId(1, 1);

        $this->repository
            ->expects(self::once())
            ->method('incrementSequence')
            ->with(
                $this->sequenceType,
                $this->discriminatorType,
                $this->discriminatorValue,
                1
            )
            ->willReturn($sequence);

        $result = $this->manager->nextNumber();
        self::assertEquals(1, $result);
    }

    public function testResetSequence(): void
    {
        $sequence = $this->createNumberSequenceWithId(1, 0);

        $this->repository
            ->expects(self::once())
            ->method('resetSequence')
            ->with(
                $this->sequenceType,
                $this->discriminatorType,
                $this->discriminatorValue,
                0
            )
            ->willReturn($sequence);

        $this->manager->resetSequence(0);
    }

    public function testResetSequenceWithCustomNumber(): void
    {
        $sequence = $this->createNumberSequenceWithId(1, 100);

        $this->repository
            ->expects(self::once())
            ->method('resetSequence')
            ->with(
                $this->sequenceType,
                $this->discriminatorType,
                $this->discriminatorValue,
                100
            )
            ->willReturn($sequence);

        $this->manager->resetSequence(100);
    }

    public function testResetSequenceThrowsOnNegativeNumber(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sequence number must be a positive integer.');

        $this->manager->resetSequence(-1);
    }

    public function testReserveSequence(): void
    {
        $sequence = $this->createNumberSequenceWithId(1, 15);

        $this->repository
            ->expects(self::once())
            ->method('incrementSequence')
            ->with(
                $this->sequenceType,
                $this->discriminatorType,
                $this->discriminatorValue,
                5
            )
            ->willReturn($sequence);

        $result = $this->manager->reserveSequence(5);
        self::assertEquals([11, 12, 13, 14, 15], $result);
    }

    public function testReserveSequenceSingleNumber(): void
    {
        $sequence = $this->createNumberSequenceWithId(1, 8);

        $this->repository
            ->expects(self::once())
            ->method('incrementSequence')
            ->with(
                $this->sequenceType,
                $this->discriminatorType,
                $this->discriminatorValue,
                1
            )
            ->willReturn($sequence);

        $result = $this->manager->reserveSequence(1);
        self::assertEquals([8], $result);
    }

    public function testReserveSequenceThrowsOnInvalidSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Size must be a positive integer.');

        $this->manager->reserveSequence(0);
    }

    public function testReserveSequenceThrowsOnNegativeSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Size must be a positive integer.');

        $this->manager->reserveSequence(-5);
    }

    public function testReserveSequenceLargeRange(): void
    {
        $sequence = $this->createNumberSequenceWithId(1, 110);

        $this->repository
            ->expects(self::once())
            ->method('incrementSequence')
            ->with(
                $this->sequenceType,
                $this->discriminatorType,
                $this->discriminatorValue,
                10
            )
            ->willReturn($sequence);

        $result = $this->manager->reserveSequence(10);
        self::assertEquals([101, 102, 103, 104, 105, 106, 107, 108, 109, 110], $result);
    }
}
