<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Manager;

use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\DraftSession\Manager\EntityDraftLoader;
use Oro\Component\DraftSession\Provider\DraftSessionUuidProvider;
use Oro\Component\DraftSession\Provider\EntityDraftRepositoryInterface;
use Oro\Component\DraftSession\Synchronizer\EntityDraftSynchronizerInterface;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EntityDraftLoaderTest extends TestCase
{
    use LoggerAwareTraitTestTrait;

    private EntityDraftRepositoryInterface&MockObject $entityDraftRepository;

    private DraftSessionUuidProvider&MockObject $draftSessionUuidProvider;

    private EntityDraftSynchronizerInterface&MockObject $entityDraftSynchronizer;

    private EntityDraftLoader $loader;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityDraftRepository = $this->createMock(EntityDraftRepositoryInterface::class);
        $this->draftSessionUuidProvider = $this->createMock(DraftSessionUuidProvider::class);
        $this->entityDraftSynchronizer = $this->createMock(EntityDraftSynchronizerInterface::class);

        $this->loader = new EntityDraftLoader(
            $this->entityDraftRepository,
            $this->draftSessionUuidProvider,
            $this->entityDraftSynchronizer
        );

        $this->setUpLoggerMock($this->loader);
    }

    public function testLoadFromEntityDraftSynchronizesExistingSourceWhenInputIsDraft(): void
    {
        $entityDraft = new EntityDraftAwareStub(100);
        $entityDraft->setDraftSessionUuid('draft-uuid');

        $entity = new EntityDraftAwareStub(10);
        $entityDraft->setDraftSource($entity);

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeFromDraft')
            ->with($entityDraft, $entity);

        $debugLogs = [];
        $this->loggerMock
            ->expects(self::atLeastOnce())
            ->method('debug')
            ->willReturnCallback(static function (string $message, array $context = []) use (&$debugLogs): void {
                $debugLogs[] = ['message' => $message, 'context' => $context];
            });

        self::assertSame($entity, $this->loader->loadFromEntityDraft($entityDraft));

        self::assertLogRecordContains(
            $debugLogs,
            'Entity draft load was started for {entity_class}.',
            [
                'entity_class' => EntityDraftAwareStub::class,
                'entity_id' => 100,
                'draft_session_uuid' => null,
                'is_draft_input' => true,
            ]
        );
        self::assertLogRecordContains(
            $debugLogs,
            'Draft input was detected for {entity_class}.',
            [
                'entity_class' => EntityDraftAwareStub::class,
                'draft_id' => 100,
                'draft_session_uuid' => 'draft-uuid',
            ]
        );
        self::assertLogRecordContains(
            $debugLogs,
            'Existing entity {entity_id} was synchronized from draft {draft_id}.',
            [
                'entity_id' => 10,
                'draft_id' => 100,
                'draft_session_uuid' => 'draft-uuid',
            ]
        );
    }

    /**
     * @param list<array{message: string, context: array}> $records
     * @param array<string, mixed> $expectedContext
     */
    private static function assertLogRecordContains(array $records, string $message, array $expectedContext): void
    {
        foreach ($records as $record) {
            if ($record['message'] !== $message) {
                continue;
            }

            foreach ($expectedContext as $key => $value) {
                if (!array_key_exists($key, $record['context']) || $record['context'][$key] !== $value) {
                    continue 2;
                }
            }

            self::assertTrue(true);

            return;
        }

        self::fail(sprintf('Expected log record not found. Message: "%s"', $message));
    }

    public function testLoadFromEntityDraftDoesNotSynchronizeWhenDraftSourceIsNewEntity(): void
    {
        $entityDraft = new EntityDraftAwareStub(100);
        $entityDraft->setDraftSessionUuid('draft-uuid');

        $newEntity = new EntityDraftAwareStub();
        $entityDraft->setDraftSource($newEntity);

        $this->entityDraftSynchronizer
            ->expects(self::never())
            ->method('synchronizeFromDraft');

        self::assertSame($newEntity, $this->loader->loadFromEntityDraft($entityDraft));
    }

    public function testLoadFromEntityDraftInstantiatesAndSynchronizesNewEntityWhenDraftHasNoSource(): void
    {
        $entityDraft = new EntityDraftAwareStub(100);
        $entityDraft->setDraftSessionUuid('draft-uuid');

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeFromDraft')
            ->with(
                $entityDraft,
                self::callback(static fn (object $entity): bool => $entity instanceof EntityDraftAwareStub)
            );

        $entity = $this->loader->loadFromEntityDraft($entityDraft);

        self::assertInstanceOf(EntityDraftAwareStub::class, $entity);
        self::assertNotSame($entityDraft, $entity);
    }

    public function testLoadFromEntityDraftSynchronizesFromRepositoryDraftWhenInputIsEntity(): void
    {
        $entity = new EntityDraftAwareStub(10);
        $entityDraft = new EntityDraftAwareStub(100);
        $entityDraft->setDraftSessionUuid('explicit-uuid');

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($entity, 'explicit-uuid')
            ->willReturn($entityDraft);

        $this->entityDraftSynchronizer
            ->expects(self::once())
            ->method('synchronizeFromDraft')
            ->with($entityDraft, $entity);

        self::assertSame($entity, $this->loader->loadFromEntityDraft($entity, 'explicit-uuid'));
    }

    public function testLoadFromEntityDraftReturnsEntityAsIsWhenNoDraftExists(): void
    {
        $entity = new EntityDraftAwareStub(10);

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($entity, 'explicit-uuid')
            ->willReturn(null);

        $this->entityDraftSynchronizer
            ->expects(self::never())
            ->method('synchronizeFromDraft');

        self::assertSame($entity, $this->loader->loadFromEntityDraft($entity, 'explicit-uuid'));
    }

    public function testLoadFromEntityDraftUsesProviderWhenSessionUuidIsNotPassed(): void
    {
        $entity = new EntityDraftAwareStub(10);

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('provider-uuid');

        $this->entityDraftRepository
            ->expects(self::once())
            ->method('findEntityDraft')
            ->with($entity, 'provider-uuid')
            ->willReturn(null);

        self::assertSame($entity, $this->loader->loadFromEntityDraft($entity));
    }

    public function testLoadFromEntityDraftSkipsRegularEntityLookupWhenResolvedSessionUuidIsNull(): void
    {
        $entity = new EntityDraftAwareStub(10);

        $this->draftSessionUuidProvider
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $this->entityDraftRepository
            ->expects(self::never())
            ->method('findEntityDraft');

        $this->entityDraftSynchronizer
            ->expects(self::never())
            ->method('synchronizeFromDraft');

        self::assertSame($entity, $this->loader->loadFromEntityDraft($entity));
    }
}
