<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\EventListener\MenuUpdateChangeCollector;
use Oro\Bundle\DataAuditBundle\Service\AuditEntryRecorder;
use Oro\Bundle\DataAuditBundle\Tests\Unit\Stub\MenuItemConditionStub;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MenuUpdateChangeCollectorTest extends TestCase
{
    private const string MENU = 'application_menu';
    private const string ITEM_KEY = 'contact_us';
    private const string IMAGE = 'image';
    private const string CONDITIONS = 'menuItemConditions';

    private EntityManagerInterface&MockObject $entityManager;
    private UnitOfWork&MockObject $unitOfWork;
    private OnFlushEventArgs $eventArgs;
    private MenuUpdate $menuUpdate;
    private MenuUpdateChangeCollector $collector;
    private array $fieldValues = [];
    private array $changeSets = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->menuUpdate = new MenuUpdate();
        $this->menuUpdate->setMenu(self::MENU);
        $this->menuUpdate->setKey(self::ITEM_KEY);
        $this->menuUpdate->setScope($this->createScope(5));

        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $this->unitOfWork->expects(self::any())
            ->method('getIdentityMap')
            ->willReturn([MenuUpdate::class => [$this->menuUpdate]]);
        $this->unitOfWork->expects(self::any())
            ->method('getEntityChangeSet')
            ->willReturnCallback(fn (object $entity): array => $this->changeSets[spl_object_id($entity)] ?? []);

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);
        $this->entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturnCallback(fn (string $class): ClassMetadata => match ($class) {
                MenuUpdate::class => $this->createMenuUpdateMetadata(),
                default => $this->createRelatedMetadata($class),
            });

        $this->eventArgs = new OnFlushEventArgs($this->entityManager);

        $recorder = $this->createMock(AuditEntryRecorder::class);
        $recorder->expects(self::any())
            ->method('isEnabled')
            ->willReturn(true);

        $this->collector = new MenuUpdateChangeCollector($recorder, MenuUpdate::class);
    }

    public function testRecordsTheImageAMenuItemGotInsteadOfTheOneItHad(): void
    {
        $image = $this->createFile('cover.png');
        $this->fieldValues[self::IMAGE] = $image;
        $this->changeSets[spl_object_id($image)] = ['originalFilename' => ['cover.png', 'new-cover.png']];
        $this->givenScheduled(updates: [$image]);

        $this->collector->onFlush($this->eventArgs);

        [$old, $new] = $this->getChange(self::IMAGE);
        self::assertNotSame($image, $old);
        self::assertSame('cover.png', $old->getOriginalFilename());
        self::assertSame($image, $new);
    }

    public function testRecordsTheImageAMenuItemNoLongerHas(): void
    {
        $image = $this->createFile('cover.png');
        $image->setEmptyFile(true);
        $this->fieldValues[self::IMAGE] = $image;
        $this->givenScheduled(updates: [$image]);

        $this->collector->onFlush($this->eventArgs);

        self::assertSame([$image, null], $this->getChange(self::IMAGE));
    }

    public function testRecordsTheImageAMenuItemLostWithIt(): void
    {
        $image = $this->createFile('cover.png');
        $this->fieldValues[self::IMAGE] = $image;
        $this->givenScheduled(deletions: [$image]);

        $this->collector->onFlush($this->eventArgs);

        self::assertSame([$image, null], $this->getChange(self::IMAGE));
    }

    public function testRecordsTheConditionsAMenuItemIsShownUnderAsAWhole(): void
    {
        $kept = new MenuItemConditionStub('contains "Mobile"');
        $added = new MenuItemConditionStub('matches "iPhone"');
        $removed = new MenuItemConditionStub('contains "Bot"');

        $collection = $this->createCollection([$kept, $added], [$kept, $removed]);
        $this->givenScheduled(collectionUpdates: [$collection]);

        $this->collector->onFlush($this->eventArgs);

        self::assertSame([[$kept, $removed], [$kept, $added]], $this->getChange(self::CONDITIONS));
    }

    public function testRecordsTheConditionsAMenuItemIsShownUnderWhenOneOfThemIsEdited(): void
    {
        $edited = new MenuItemConditionStub('matches "iPhone"');
        $untouched = new MenuItemConditionStub('contains "Mobile"');
        $this->fieldValues[self::CONDITIONS] = new ArrayCollection([$untouched, $edited]);
        $this->changeSets[spl_object_id($edited)] = ['value' => ['matches "iPad"', 'matches "iPhone"']];
        $this->givenScheduled(updates: [$edited]);

        $this->collector->onFlush($this->eventArgs);

        [$old, $new] = $this->getChange(self::CONDITIONS);
        self::assertSame([$untouched, $edited], $new);
        self::assertSame($untouched, $old[0]);
        self::assertSame('matches "iPad"', $old[1]->getValue());
    }

    public function testRecordsTheConditionsAsTheyWereWhenOneIsEditedAndAnotherIsAddedAtOnce(): void
    {
        $edited = new MenuItemConditionStub('matches "iPhone"');
        $added = new MenuItemConditionStub('contains "Bot"');
        $this->changeSets[spl_object_id($edited)] = ['value' => ['matches "iPad"', 'matches "iPhone"']];

        $collection = $this->createCollection([$edited, $added], [$edited]);
        $this->fieldValues[self::CONDITIONS] = $collection;
        $this->givenScheduled(updates: [$edited], collectionUpdates: [$collection]);

        $this->collector->onFlush($this->eventArgs);

        [$old, $new] = $this->getChange(self::CONDITIONS);
        self::assertSame([$edited, $added], $new);
        self::assertCount(1, $old);
        self::assertSame('matches "iPad"', $old[0]->getValue());
    }

    public function testRecordsNothingAboutAValueNoMenuItemOwns(): void
    {
        $condition = new MenuItemConditionStub('contains "Mobile"');
        $this->fieldValues[self::CONDITIONS] = new ArrayCollection([new MenuItemConditionStub('contains "Bot"')]);
        $this->givenScheduled(updates: [$condition], deletions: [$this->createFile('cover.png')]);

        $this->collector->onFlush($this->eventArgs);

        self::assertSame([], $this->collector->release());
    }

    public function testRecordsARemovedMenuItemByWhatItIsMappedWithAndNotByItsForeignKeys(): void
    {
        $image = $this->createFile('cover.png');
        $this->unitOfWork->expects(self::any())
            ->method('getOriginalEntityData')
            ->willReturn([
                'uri' => '/contact-us',
                self::IMAGE => $image,
                'image_id' => 12,
                'scope_id' => 5,
                'content_node_id' => null,
            ]);
        $this->givenScheduled(deletions: [$this->menuUpdate]);

        $this->collector->onFlush($this->eventArgs);

        $collected = $this->collector->release();
        $item = reset($collected)['items'][self::ITEM_KEY];

        self::assertSame(Audit::ACTION_REMOVE, $item['action']);
        self::assertSame(['uri', self::IMAGE], array_keys($item['changeSet']));
        self::assertSame(['/contact-us', null], $item['changeSet']['uri']);
        self::assertSame([$image, null], $item['changeSet'][self::IMAGE]);
    }

    private function getChange(string $field): array
    {
        $collected = $this->collector->release();
        $items = reset($collected)['items'] ?? [];

        self::assertArrayHasKey(self::ITEM_KEY, $items);
        self::assertSame(Audit::ACTION_UPDATE, $items[self::ITEM_KEY]['action']);
        self::assertArrayHasKey($field, $items[self::ITEM_KEY]['changeSet']);

        return $items[self::ITEM_KEY]['changeSet'][$field];
    }

    private function givenScheduled(
        array $updates = [],
        array $deletions = [],
        array $collectionUpdates = []
    ): void {
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updates);
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletions);
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledCollectionUpdates')
            ->willReturn($collectionUpdates);
    }

    private function createCollection(array $items, array $snapshot): PersistentCollection
    {
        $collection = new PersistentCollection(
            $this->entityManager,
            MenuItemConditionStub::class,
            new ArrayCollection($items)
        );
        $collection->setOwner($this->menuUpdate, [
            'fieldName' => self::CONDITIONS,
            'targetEntity' => MenuItemConditionStub::class,
            'inversedBy' => null,
            'mappedBy' => null,
        ]);
        ReflectionUtil::setPropertyValue($collection, 'snapshot', $snapshot);

        return $collection;
    }

    private function createFile(string $originalFilename): File
    {
        $file = new File();
        $file->setOriginalFilename($originalFilename);
        $file->setFilename(md5($originalFilename) . '.png');

        return $file;
    }

    private function createScope(int $id): Scope
    {
        $scope = new Scope();
        ReflectionUtil::setId($scope, $id);

        return $scope;
    }

    private function createMenuUpdateMetadata(): ClassMetadata
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $associations = [
            'scope' => false,
            'titles' => true,
            'descriptions' => true,
            self::IMAGE => false,
            self::CONDITIONS => true,
        ];
        $metadata->expects(self::any())
            ->method('getAssociationNames')
            ->willReturn(array_keys($associations));
        $metadata->expects(self::any())
            ->method('isCollectionValuedAssociation')
            ->willReturnCallback(static fn (string $field): bool => $associations[$field] ?? false);
        $metadata->expects(self::any())
            ->method('getAssociationTargetClass')
            ->willReturnCallback(static fn (string $field): string => match ($field) {
                'scope' => Scope::class,
                'titles', 'descriptions' => LocalizedFallbackValue::class,
                self::IMAGE => File::class,
                default => MenuItemConditionStub::class,
            });
        $metadata->expects(self::any())
            ->method('getFieldValue')
            ->willReturnCallback(fn (object $entity, string $field): mixed => $this->fieldValues[$field] ?? null);
        $metadata->expects(self::any())
            ->method('hasAssociation')
            ->willReturnCallback(static fn (string $field): bool => isset($associations[$field]));
        $metadata->expects(self::any())
            ->method('hasField')
            ->willReturnCallback(
                static fn (string $field): bool => !isset($associations[$field]) && !str_ends_with($field, '_id')
            );

        return $metadata;
    }

    private function createRelatedMetadata(string $class): ClassMetadata
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::any())
            ->method('getName')
            ->willReturn($class);
        $metadata->expects(self::any())
            ->method('hasField')
            ->willReturn(true);
        $metadata->expects(self::any())
            ->method('setFieldValue')
            ->willReturnCallback(
                static fn (object $entity, string $field, mixed $value) => ReflectionUtil::setPropertyValue(
                    $entity,
                    $field,
                    $value
                )
            );

        return $metadata;
    }
}
