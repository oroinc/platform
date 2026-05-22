<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Provider;

use Oro\Component\DraftSession\Provider\EntityDraftRepositoryChain;
use Oro\Component\DraftSession\Provider\EntityDraftRepositoryInterface;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EntityDraftRepositoryChainTest extends TestCase
{
    private EntityDraftRepositoryInterface&MockObject $entityDraftRepository;

    private EntityDraftRepositoryChain $repository;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityDraftRepository = $this->createMock(EntityDraftRepositoryInterface::class);

        $this->repository = new EntityDraftRepositoryChain([$this->entityDraftRepository]);
    }

    public function testSupportsReturnsFalseWhenNoRepositorySupportsClass(): void
    {
        $this->entityDraftRepository
            ->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(false);

        self::assertFalse($this->repository->supports(EntityDraftAwareStub::class));
    }

    public function testHasEntityDraftDelegatesToSupportedRepository(): void
    {
        $entity = new EntityDraftAwareStub(42);

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('hasEntityDraft')
            ->with($entity, 'draft-session-uuid')
            ->willReturn(true);

        self::assertTrue($this->repository->hasEntityDraft($entity, 'draft-session-uuid'));
    }

    public function testHasEntityDraftReturnsFalseWhenNoRepositorySupportsEntityClass(): void
    {
        $entity = new EntityDraftAwareStub(42);

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(false);

        $this->entityDraftRepository
            ->expects(self::never())
            ->method('hasEntityDraft');

        self::assertFalse($this->repository->hasEntityDraft($entity, 'draft-session-uuid'));
    }

    public function testFindEntityDraftDelegatesToSupportedRepository(): void
    {
        $entity = new EntityDraftAwareStub(42);
        $draft = new EntityDraftAwareStub(100);

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(true);

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($entity, 'draft-session-uuid')
            ->willReturn($draft);

        self::assertSame($draft, $this->repository->findEntityDraft($entity, 'draft-session-uuid'));
    }

    public function testFindEntityDraftReturnsNullWhenNoRepositorySupportsEntityClass(): void
    {
        $entity = new EntityDraftAwareStub(42);

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('supports')
            ->with(EntityDraftAwareStub::class)
            ->willReturn(false);

        $this->entityDraftRepository
            ->expects(self::never())
            ->method('findEntityDraft');

        self::assertNull($this->repository->findEntityDraft($entity, 'draft-session-uuid'));
    }
}
