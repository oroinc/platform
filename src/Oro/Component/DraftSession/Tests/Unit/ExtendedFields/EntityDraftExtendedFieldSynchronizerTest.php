<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\Tests\Unit\ExtendedFields;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Component\DraftSession\Doctrine\EntityDraftSyncReferenceResolver;
use Oro\Component\DraftSession\ExtendedFields\EntityDraftExtendedFieldSynchronizer;
use Oro\Component\DraftSession\Tests\Unit\Stub\EntityDraftAwareStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class EntityDraftExtendedFieldSynchronizerTest extends TestCase
{
    private EntityDraftSyncReferenceResolver&MockObject $referenceResolver;
    private FieldHelper&MockObject $fieldHelper;
    private EntityDraftExtendedFieldSynchronizer $synchronizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->referenceResolver = $this->createMock(EntityDraftSyncReferenceResolver::class);
        $this->fieldHelper = $this->createMock(FieldHelper::class);

        $this->synchronizer = new EntityDraftExtendedFieldSynchronizer(
            $this->referenceResolver,
            $this->fieldHelper,
        );
    }

    public function testSynchronizeScalarStringField(): void
    {
        $source = new EntityDraftAwareStub();
        $target = new EntityDraftAwareStub();

        $this->fieldHelper->expects(self::once())
            ->method('getObjectValue')
            ->with($source, 'customText')
            ->willReturn('hello world');

        $this->fieldHelper->expects(self::once())
            ->method('setObjectValue')
            ->with($target, 'customText', 'hello world');

        $this->referenceResolver->expects(self::never())
            ->method('getReference');

        $this->synchronizer->synchronize($source, $target, 'customText', 'string');
    }

    public function testSynchronizeScalarFieldWithNullValue(): void
    {
        $source = new EntityDraftAwareStub();
        $target = new EntityDraftAwareStub();

        $this->fieldHelper->expects(self::once())
            ->method('getObjectValue')
            ->with($source, 'customText')
            ->willReturn(null);

        $this->fieldHelper->expects(self::once())
            ->method('setObjectValue')
            ->with($target, 'customText', null);

        $this->referenceResolver->expects(self::never())
            ->method('getReference');

        $this->synchronizer->synchronize($source, $target, 'customText', 'string');
    }

    public function testSynchronizeScalarFieldClonesObjectValue(): void
    {
        $source = new EntityDraftAwareStub();
        $target = new EntityDraftAwareStub();
        $originalDate = new \DateTime('2024-01-01');

        $this->fieldHelper->expects(self::once())
            ->method('getObjectValue')
            ->with($source, 'dateField')
            ->willReturn($originalDate);

        $this->fieldHelper->expects(self::once())
            ->method('setObjectValue')
            ->with(
                $target,
                'dateField',
                self::logicalAnd(
                    self::isInstanceOf(\DateTime::class),
                    self::logicalNot(self::identicalTo($originalDate))
                )
            );

        $this->referenceResolver->expects(self::never())
            ->method('getReference');

        $this->synchronizer->synchronize($source, $target, 'dateField', 'string');
    }

    public function testSynchronizeManyToOneRelationField(): void
    {
        $source = new EntityDraftAwareStub();
        $target = new EntityDraftAwareStub();
        $relatedEntity = new EntityDraftAwareStub();
        $reference = new EntityDraftAwareStub();

        $this->fieldHelper->expects(self::once())
            ->method('getObjectValue')
            ->with($source, 'relatedOrder')
            ->willReturn($relatedEntity);

        $this->referenceResolver->expects(self::once())
            ->method('getReference')
            ->with($relatedEntity)
            ->willReturn($reference);

        $this->fieldHelper->expects(self::once())
            ->method('setObjectValue')
            ->with($target, 'relatedOrder', $reference);

        $this->synchronizer->synchronize($source, $target, 'relatedOrder', 'manyToOne');
    }

    public function testSynchronizeEnumRelationField(): void
    {
        $source = new EntityDraftAwareStub();
        $target = new EntityDraftAwareStub();
        $enumValue = new EntityDraftAwareStub();
        $enumReference = new EntityDraftAwareStub();

        $this->fieldHelper->expects(self::once())
            ->method('getObjectValue')
            ->with($source, 'customEnum')
            ->willReturn($enumValue);

        $this->referenceResolver->expects(self::once())
            ->method('getReference')
            ->with($enumValue)
            ->willReturn($enumReference);

        $this->fieldHelper->expects(self::once())
            ->method('setObjectValue')
            ->with($target, 'customEnum', $enumReference);

        $this->synchronizer->synchronize($source, $target, 'customEnum', 'enum');
    }

    public function testSynchronizeManyToManyCollectionField(): void
    {
        $source = new EntityDraftAwareStub();
        $target = new EntityDraftAwareStub();
        $item1 = new EntityDraftAwareStub();
        $item2 = new EntityDraftAwareStub();
        $ref1 = new EntityDraftAwareStub();
        $ref2 = new EntityDraftAwareStub();
        $sourceCollection = new ArrayCollection([$item1, $item2]);
        $targetCollection = new ArrayCollection([new EntityDraftAwareStub()]);

        $this->fieldHelper->expects(self::exactly(2))
            ->method('getObjectValue')
            ->willReturnMap([
                [$source, 'multiField', $sourceCollection],
                [$target, 'multiField', $targetCollection],
            ]);

        $this->referenceResolver->expects(self::exactly(2))
            ->method('getReference')
            ->willReturnMap([
                [$item1, $ref1],
                [$item2, $ref2],
            ]);

        $this->fieldHelper->expects(self::never())
            ->method('setObjectValue');

        $this->synchronizer->synchronize($source, $target, 'multiField', 'manyToMany');

        self::assertCount(2, $targetCollection);
        self::assertSame($ref1, $targetCollection->get(0));
        self::assertSame($ref2, $targetCollection->get(1));
    }

    public function testSynchronizeMultiEnumFieldAsCollection(): void
    {
        $source = new EntityDraftAwareStub();
        $target = new EntityDraftAwareStub();
        $item = new EntityDraftAwareStub();
        $ref = new EntityDraftAwareStub();
        $sourceCollection = new ArrayCollection([$item]);
        $targetCollection = new ArrayCollection();

        $this->fieldHelper->expects(self::exactly(2))
            ->method('getObjectValue')
            ->willReturnMap([
                [$source, 'multiEnumField', $sourceCollection],
                [$target, 'multiEnumField', $targetCollection],
            ]);

        $this->referenceResolver->expects(self::once())
            ->method('getReference')
            ->with($item)
            ->willReturn($ref);

        $this->synchronizer->synchronize($source, $target, 'multiEnumField', 'multiEnum');

        self::assertCount(1, $targetCollection);
        self::assertSame($ref, $targetCollection->first());
    }

    public function testSynchronizeCollectionFieldSkipsNullReferences(): void
    {
        $source = new EntityDraftAwareStub();
        $target = new EntityDraftAwareStub();
        $item1 = new EntityDraftAwareStub();
        $item2 = new EntityDraftAwareStub();
        $ref1 = new EntityDraftAwareStub();
        $sourceCollection = new ArrayCollection([$item1, $item2]);
        $targetCollection = new ArrayCollection();

        $this->fieldHelper->expects(self::exactly(2))
            ->method('getObjectValue')
            ->willReturnMap([
                [$source, 'multiField', $sourceCollection],
                [$target, 'multiField', $targetCollection],
            ]);

        $this->referenceResolver->expects(self::exactly(2))
            ->method('getReference')
            ->willReturnMap([
                [$item1, $ref1],
                [$item2, null],
            ]);

        $this->synchronizer->synchronize($source, $target, 'multiField', 'manyToMany');

        self::assertCount(1, $targetCollection);
        self::assertSame($ref1, $targetCollection->first());
    }

    public function testSynchronizeCollectionFieldNoOpWhenSourceNotCollection(): void
    {
        $source = new EntityDraftAwareStub();
        $target = new EntityDraftAwareStub();
        $existingItem = new EntityDraftAwareStub();
        $targetCollection = new ArrayCollection([$existingItem]);

        $this->fieldHelper->expects(self::exactly(2))
            ->method('getObjectValue')
            ->willReturnMap([
                [$source, 'multiField', null],
                [$target, 'multiField', $targetCollection],
            ]);

        $this->referenceResolver->expects(self::never())
            ->method('getReference');

        $this->synchronizer->synchronize($source, $target, 'multiField', 'manyToMany');

        self::assertCount(1, $targetCollection);
        self::assertSame($existingItem, $targetCollection->first());
    }

    public function testSynchronizeCollectionFieldNoOpWhenTargetNotCollection(): void
    {
        $source = new EntityDraftAwareStub();
        $target = new EntityDraftAwareStub();
        $existingItem = new EntityDraftAwareStub();
        $sourceCollection = new ArrayCollection([$existingItem]);

        $this->fieldHelper->expects(self::exactly(2))
            ->method('getObjectValue')
            ->willReturnMap([
                [$source, 'multiField', $sourceCollection],
                [$target, 'multiField', null],
            ]);

        $this->referenceResolver->expects(self::never())
            ->method('getReference');

        $this->synchronizer->synchronize($source, $target, 'multiField', 'manyToMany');

        self::assertCount(1, $sourceCollection);
        self::assertSame($existingItem, $sourceCollection->first());
    }
}
