<?php

declare(strict_types=1);

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PlatformBundle\Entity\NumberSequence;
use Oro\Bundle\PlatformBundle\Entity\Repository\NumberSequenceRepository;
use Oro\Bundle\PlatformBundle\Event\DeleteOldNumberSequenceEvent;
use Oro\Bundle\PlatformBundle\EventListener\DeleteOldNumberSequenceEventListener;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class DeleteOldNumberSequenceEventListenerTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private const string SEQUENCE_TYPE = 'invoice';
    private const string DISCRIMINATOR_TYPE = 'organization_periodic';

    private EntityManagerInterface&MockObject $entityManager;
    private NumberSequenceRepository&MockObject $repository;
    private ManagerRegistry&MockObject $doctrine;
    private DeleteOldNumberSequenceEventListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(NumberSequenceRepository::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->doctrine
            ->expects(self::any())
            ->method('getRepository')
            ->with(NumberSequence::class)
            ->willReturn($this->repository);

        $this->doctrine
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with(NumberSequence::class)
            ->willReturn($this->entityManager);

        $this->listener = new DeleteOldNumberSequenceEventListener(
            $this->doctrine,
            self::SEQUENCE_TYPE,
            self::DISCRIMINATOR_TYPE
        );

        $this->setUpLoggerMock($this->listener);
    }

    public function testOnDeleteOldNumberSequenceIgnoresDifferentTypes(): void
    {
        $this->repository
            ->expects(self::never())
            ->method('findByTypeAndDiscriminatorOrdered');

        $this->listener->onDeleteOldNumberSequence(
            new DeleteOldNumberSequenceEvent('different_type', self::DISCRIMINATOR_TYPE)
        );
    }

    public function testOnDeleteOldNumberSequenceIgnoresDifferentDiscriminators(): void
    {
        $this->repository
            ->expects(self::never())
            ->method('findByTypeAndDiscriminatorOrdered');

        $this->listener->onDeleteOldNumberSequence(
            new DeleteOldNumberSequenceEvent(self::SEQUENCE_TYPE, 'different_discriminator')
        );
    }

    public function testOnDeleteOldNumberSequenceDoesNothingWhenNoSequencesFound(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findByTypeAndDiscriminatorOrdered')
            ->with(self::SEQUENCE_TYPE, self::DISCRIMINATOR_TYPE)
            ->willReturn([]);

        $this->entityManager
            ->expects(self::never())
            ->method('remove');

        $this->entityManager
            ->expects(self::never())
            ->method('flush');

        $this->listener->onDeleteOldNumberSequence(
            new DeleteOldNumberSequenceEvent(self::SEQUENCE_TYPE, self::DISCRIMINATOR_TYPE)
        );
    }

    public function testOnDeleteOldNumberSequenceDoesNothingWithSingleSequence(): void
    {
        $sequence = $this->createMock(NumberSequence::class);

        $this->repository
            ->expects(self::once())
            ->method('findByTypeAndDiscriminatorOrdered')
            ->with(self::SEQUENCE_TYPE, self::DISCRIMINATOR_TYPE)
            ->willReturn([$sequence]);

        $this->entityManager
            ->expects(self::never())
            ->method('remove');

        $this->entityManager
            ->expects(self::never())
            ->method('flush');

        $this->listener->onDeleteOldNumberSequence(
            new DeleteOldNumberSequenceEvent(self::SEQUENCE_TYPE, self::DISCRIMINATOR_TYPE)
        );
    }

    public function testOnDeleteOldNumberSequenceDeletesOldSequences(): void
    {
        $newestSequence = $this->createMock(NumberSequence::class);
        $oldSequence1 = $this->createMock(NumberSequence::class);
        $oldSequence2 = $this->createMock(NumberSequence::class);

        $this->repository
            ->expects(self::once())
            ->method('findByTypeAndDiscriminatorOrdered')
            ->with(self::SEQUENCE_TYPE, self::DISCRIMINATOR_TYPE)
            ->willReturn([$newestSequence, $oldSequence1, $oldSequence2]);

        $this->entityManager
            ->expects(self::exactly(2))
            ->method('remove')
            ->withConsecutive(
                [$oldSequence1],
                [$oldSequence2]
            );

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $this->listener->onDeleteOldNumberSequence(
            new DeleteOldNumberSequenceEvent(self::SEQUENCE_TYPE, self::DISCRIMINATOR_TYPE)
        );
    }

    public function testOnDeleteOldNumberSequenceLogsException(): void
    {
        $exception = new \RuntimeException('Database error');

        $this->repository
            ->expects(self::once())
            ->method('findByTypeAndDiscriminatorOrdered')
            ->with(self::SEQUENCE_TYPE, self::DISCRIMINATOR_TYPE)
            ->willThrowException($exception);

        $this->loggerMock
            ->expects(self::once())
            ->method('error')
            ->with(
                'Failed to delete old number sequences for sequenceType: {sequenceType},'
                . 'discriminatorType: {discriminatorType}',
                [
                    'sequenceType' => self::SEQUENCE_TYPE,
                    'discriminatorType' => self::DISCRIMINATOR_TYPE,
                    'exception' => 'Database error',
                ]
            );

        $this->listener->onDeleteOldNumberSequence(
            new DeleteOldNumberSequenceEvent(self::SEQUENCE_TYPE, self::DISCRIMINATOR_TYPE)
        );
    }
}
