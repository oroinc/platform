<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\Util;

use Oro\Component\DraftSession\Exception\DraftSessionLogicException;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use Oro\Component\DraftSession\Util\EntityDraftUtils;
use PHPUnit\Framework\TestCase;

final class EntityDraftUtilsTest extends TestCase
{
    public function testIsEntityDraftReturnsTrueWhenDraftSessionUuidIsPresent(): void
    {
        $entityDraft = new EntityDraftAwareStub();
        $entityDraft->setDraftSessionUuid('draft-session-uuid');

        self::assertTrue(EntityDraftUtils::isEntityDraft($entityDraft));
    }

    public function testIsEntityDraftReturnsFalseWhenDraftSessionUuidIsMissing(): void
    {
        $entity = new EntityDraftAwareStub();

        self::assertFalse(EntityDraftUtils::isEntityDraft($entity));
    }

    public function testGetEntityFromDraftReturnsEntityWhenArgumentIsNotDraft(): void
    {
        $entity = new EntityDraftAwareStub(42);

        self::assertSame($entity, EntityDraftUtils::getEntityFromDraft($entity));
    }

    public function testGetEntityFromDraftReturnsDraftSourceWhenArgumentIsDraft(): void
    {
        $entity = new EntityDraftAwareStub(42);
        $entityDraft = new EntityDraftAwareStub(100);
        $entityDraft->setDraftSessionUuid('draft-session-uuid');
        $entityDraft->setDraftSource($entity);

        self::assertSame($entity, EntityDraftUtils::getEntityFromDraft($entityDraft));
    }

    public function testGetEntityFromDraftThrowsExceptionWhenDraftSourceIsMissing(): void
    {
        $entityDraft = new EntityDraftAwareStub(100);
        $entityDraft->setDraftSessionUuid('draft-session-uuid');

        $this->expectException(DraftSessionLogicException::class);
        $this->expectExceptionMessage('Entity draft is expected to reference its source entity.');

        EntityDraftUtils::getEntityFromDraft($entityDraft);
    }

    public function testGetEntityOrDraftIdReturnsRegularEntityIdWhenEntityExists(): void
    {
        $entity = new EntityDraftAwareStub(42);

        self::assertSame(42, EntityDraftUtils::getEntityOrDraftId($entity));
    }

    public function testGetEntityOrDraftIdReturnsNullForNewEntityWithoutDraft(): void
    {
        $entity = new EntityDraftAwareStub();

        self::assertNull(EntityDraftUtils::getEntityOrDraftId($entity));
    }

    public function testGetEntityOrDraftIdReturnsDraftIdForNewEntityWithDraft(): void
    {
        $entity = new EntityDraftAwareStub();

        $entityDraft = new EntityDraftAwareStub(777);
        $entityDraft->setDraftSessionUuid('draft-session-uuid');

        $entity->addDraft($entityDraft);

        self::assertSame(777, EntityDraftUtils::getEntityOrDraftId($entity));
    }

    public function testGetEntityOrDraftIdThrowsExceptionForNewEntityWithDraftWithoutId(): void
    {
        $entity = new EntityDraftAwareStub();

        $entityDraft = new EntityDraftAwareStub();
        $entityDraft->setDraftSessionUuid('draft-session-uuid');

        $entity->addDraft($entityDraft);

        $this->expectException(DraftSessionLogicException::class);
        $this->expectExceptionMessage('Entity draft is expected to have an ID.');

        EntityDraftUtils::getEntityOrDraftId($entity);
    }

    public function testGetEntityOrDraftIdReturnsDraftIdWhenArgumentIsDraft(): void
    {
        $entityDraft = new EntityDraftAwareStub(888);
        $entityDraft->setDraftSessionUuid('draft-session-uuid');

        self::assertSame(888, EntityDraftUtils::getEntityOrDraftId($entityDraft));
    }

    public function testGetEntityOrDraftIdThrowsExceptionWhenArgumentIsDraftWithoutId(): void
    {
        $entityDraft = new EntityDraftAwareStub();
        $entityDraft->setDraftSessionUuid('draft-session-uuid');

        $this->expectException(DraftSessionLogicException::class);
        $this->expectExceptionMessage('Entity draft is expected to have an ID.');

        EntityDraftUtils::getEntityOrDraftId($entityDraft);
    }
}
