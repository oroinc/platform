<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Manager;

use Oro\Component\DraftSession\Manager\EntityDraftLoader;
use Oro\Component\DraftSession\Manager\EntityDraftManager;
use Oro\Component\DraftSession\Manager\EntityDraftPersister;
use Oro\Component\DraftSession\Manager\EntityDraftRemover;
use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;
use Oro\Component\DraftSession\Provider\EntityDraftRepositoryInterface;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EntityDraftManagerTest extends TestCase
{
    private EntityDraftRepositoryInterface&MockObject $entityDraftRepository;

    private DraftSessionUuidProvider&MockObject $draftSessionUuidProvider;

    private EntityDraftLoader&MockObject $entityDraftLoader;

    private EntityDraftPersister&MockObject $entityDraftPersister;

    private EntityDraftRemover&MockObject $entityDraftRemover;

    private EntityDraftManager $manager;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityDraftRepository = $this->createMock(EntityDraftRepositoryInterface::class);
        $this->draftSessionUuidProvider = $this->createMock(DraftSessionUuidProvider::class);
        $this->entityDraftLoader = $this->createMock(EntityDraftLoader::class);
        $this->entityDraftPersister = $this->createMock(EntityDraftPersister::class);
        $this->entityDraftRemover = $this->createMock(EntityDraftRemover::class);

        $this->manager = new EntityDraftManager(
            $this->entityDraftRepository,
            $this->draftSessionUuidProvider,
            $this->entityDraftLoader,
            $this->entityDraftPersister,
            $this->entityDraftRemover
        );
    }

    public function testHasEntityDraftUsesProviderWhenSessionUuidIsNotPassed(): void
    {
        $entity = new EntityDraftAwareStub(10);

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('provider-uuid');

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('hasEntityDraft')
            ->with($entity, 'provider-uuid')
            ->willReturn(true);

        self::assertTrue($this->manager->hasEntityDraft($entity));
    }

    public function testHasEntityDraftReturnsFalseWhenResolvedSessionUuidIsNull(): void
    {
        $entity = new EntityDraftAwareStub(10);

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $this->entityDraftRepository
            ->expects(self::never())
            ->method('hasEntityDraft');

        self::assertFalse($this->manager->hasEntityDraft($entity));
    }

    public function testHasEntityDraftUsesExplicitSessionUuid(): void
    {
        $entity = new EntityDraftAwareStub(10);

        $this->draftSessionUuidProvider
            ->expects(self::never())
            ->method('getDraftSessionUuid');

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('hasEntityDraft')
            ->with($entity, 'explicit-uuid')
            ->willReturn(false);

        self::assertFalse($this->manager->hasEntityDraft($entity, 'explicit-uuid'));
    }

    public function testFindEntityDraftUsesProviderWhenSessionUuidIsNotPassed(): void
    {
        $entity = new EntityDraftAwareStub(10);
        $entityDraft = new EntityDraftAwareStub(100);
        $entityDraft->setDraftSessionUuid('provider-uuid');

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('provider-uuid');

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($entity, 'provider-uuid')
            ->willReturn($entityDraft);

        self::assertSame($entityDraft, $this->manager->findEntityDraft($entity));
    }

    public function testFindEntityDraftReturnsNullWhenResolvedSessionUuidIsNull(): void
    {
        $entity = new EntityDraftAwareStub(10);

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $this->entityDraftRepository
            ->expects(self::never())
            ->method('findEntityDraft');

        self::assertNull($this->manager->findEntityDraft($entity));
    }

    public function testFindEntityDraftUsesExplicitSessionUuid(): void
    {
        $entity = new EntityDraftAwareStub(10);
        $entityDraft = new EntityDraftAwareStub(100);
        $entityDraft->setDraftSessionUuid('explicit-uuid');

        $this->draftSessionUuidProvider
            ->expects(self::never())
            ->method('getDraftSessionUuid');

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($entity, 'explicit-uuid')
            ->willReturn($entityDraft);

        self::assertSame($entityDraft, $this->manager->findEntityDraft($entity, 'explicit-uuid'));
    }

    public function testLoadFromEntityDraftDelegatesToLoader(): void
    {
        $entity = new EntityDraftAwareStub(10);
        $loadedEntity = new EntityDraftAwareStub(10);

        $this->entityDraftLoader
            ->expects(self::once())
            ->method('loadFromEntityDraft')
            ->with($entity, 'explicit-uuid')
            ->willReturn($loadedEntity);

        self::assertSame($loadedEntity, $this->manager->loadFromEntityDraft($entity, 'explicit-uuid'));
    }

    public function testSaveToEntityDraftDelegatesToPersister(): void
    {
        $entity = new EntityDraftAwareStub(10);
        $entityDraft = new EntityDraftAwareStub(100);

        $this->entityDraftPersister
            ->expects(self::once())
            ->method('saveToEntityDraft')
            ->with($entity, 'explicit-uuid')
            ->willReturn($entityDraft);

        self::assertSame($entityDraft, $this->manager->saveToEntityDraft($entity, 'explicit-uuid'));
    }

    public function testDeleteEntityDraftDelegatesToRemover(): void
    {
        $entity = new EntityDraftAwareStub(10);
        $this->entityDraftRemover
            ->expects(self::once())
            ->method('deleteEntityDraft')
            ->with($entity, 'explicit-uuid');

        $this->manager->deleteEntityDraft($entity, 'explicit-uuid');
    }
}
