<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\DataAuditBundle\EventListener\MenuUpdateAuditListener;
use Oro\Bundle\DataAuditBundle\EventListener\MenuUpdateChangeCollector;
use Oro\Bundle\DataAuditBundle\MenuUpdate\MenuUpdateInheritedStatePropagator;
use Oro\Bundle\DataAuditBundle\Model\AuditEntry;
use Oro\Bundle\DataAuditBundle\Model\MenuAuditValueNormalizer;
use Oro\Bundle\DataAuditBundle\Provider\MenuAuditLevelProvider;
use Oro\Bundle\DataAuditBundle\Provider\MenuItemNameProvider;
use Oro\Bundle\DataAuditBundle\Service\AuditEntryRecorder;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class MenuUpdateAuditListenerTest extends TestCase
{
    private const string MENU = 'application_menu';
    private const string OBJECT_CLASS = 'Oro\Bundle\NavigationBundle\GlobalBackOfficeMenu';

    private AuditEntryRecorder&MockObject $auditEntryRecorder;
    private MenuAuditLevelProvider&MockObject $levelProvider;
    private MenuUpdateInheritedStatePropagator&MockObject $inheritedState;
    private ObjectRepository&MockObject $repository;
    private UnitOfWork&MockObject $unitOfWork;
    private OnFlushEventArgs $eventArgs;
    private MenuUpdateChangeCollector $collector;
    private MenuUpdateAuditListener $listener;

    private array $recorded = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->auditEntryRecorder = $this->createMock(AuditEntryRecorder::class);
        $this->auditEntryRecorder->expects(self::any())
            ->method('isEnabled')
            ->willReturn(true);
        $this->auditEntryRecorder->expects(self::any())
            ->method('record')
            ->willReturnCallback(function (AuditEntry $entry, ?string $transactionId = null): void {
                $this->recorded[] = ['entry' => $entry, 'transactionId' => $transactionId];
            });

        $this->levelProvider = $this->createMock(MenuAuditLevelProvider::class);
        $this->levelProvider->expects(self::any())
            ->method('getClassForScope')
            ->willReturn(self::OBJECT_CLASS);

        $this->inheritedState = $this->createMock(MenuUpdateInheritedStatePropagator::class);

        $this->unitOfWork = $this->createMock(UnitOfWork::class);
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::any())
            ->method('hasField')
            ->willReturnCallback(static fn (string $property): bool => !str_ends_with($property, '_id'));
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->unitOfWork);
        $em->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($metadata);
        $this->eventArgs = new OnFlushEventArgs($em);

        $this->collector = new MenuUpdateChangeCollector($this->auditEntryRecorder, MenuUpdate::class);
        $this->listener = new MenuUpdateAuditListener(
            $this->auditEntryRecorder,
            $this->collector,
            $this->levelProvider,
            new MenuAuditValueNormalizer($this->createMock(EntityNameResolver::class)),
            $this->inheritedState,
            $this->createItemNameProvider()
        );
    }

    public function testRecordsOneEntryPerChangedItemUnderOneTransaction(): void
    {
        $scope = $this->createScope(5);
        $contactUs = $this->createMenuUpdate('contact_us', 'Contact Us', $scope);
        $aboutUs = $this->createMenuUpdate('about_us', 'About Us', $scope);

        $this->givenScheduled(updates: [$contactUs, $aboutUs], changeSets: [
            spl_object_id($contactUs) => ['priority' => [3, 1], 'active' => [true, false]],
            spl_object_id($aboutUs) => ['priority' => [1, 2]],
        ]);

        $this->flushAndRecord();

        self::assertCount(2, $this->recorded);
        $entry = $this->recorded[0]['entry'];
        self::assertSame(self::OBJECT_CLASS, $entry->getObjectClass());
        self::assertSame('5_' . self::MENU . '_contact_us', $entry->getObjectId());
        self::assertSame(self::MENU . ' / Contact Us', $entry->getObjectName());
        self::assertSame(Audit::ACTION_UPDATE, $entry->getAction());
        self::assertSame(
            [
                'priority' => ['field' => 'priority', 'type' => 'integer', 'old' => 3, 'new' => 1],
                'active' => ['field' => 'active', 'type' => 'boolean', 'old' => true, 'new' => false],
            ],
            $entry->getChanges()
        );
        self::assertSame('5_' . self::MENU . '_about_us', $this->recorded[1]['entry']->getObjectId());

        self::assertNotNull($this->recorded[0]['transactionId']);
        self::assertSame($this->recorded[0]['transactionId'], $this->recorded[1]['transactionId']);
    }

    public function testRecordsEveryItemHiddenTogetherWithTheOneThatWasActedOn(): void
    {
        $scope = $this->createScope(5);
        $hidden = $this->createMenuUpdate('home', 'Home', $scope);
        $child = $this->createMenuUpdate('my_account', 'My Account', $scope, 'home');
        $grandChild = $this->createMenuUpdate('addresses', 'Addresses', $scope, 'my_account');

        $this->givenScheduled(updates: [$hidden, $child, $grandChild], changeSets: [
            spl_object_id($hidden) => ['active' => [true, false]],
            spl_object_id($child) => ['active' => [true, false]],
            spl_object_id($grandChild) => ['active' => [true, false]],
        ]);

        $this->flushAndRecord();

        self::assertSame(
            [self::MENU . ' / Home', self::MENU . ' / My Account', self::MENU . ' / Addresses'],
            array_map(
                static fn (array $recorded): string => $recorded['entry']->getObjectName(),
                $this->recorded
            )
        );

        foreach ($this->recorded as $recorded) {
            self::assertSame(['active'], array_keys($recorded['entry']->getChanges()));
            self::assertSame($this->recorded[0]['transactionId'], $recorded['transactionId']);
        }
    }

    public function testRecordsAnItemRemovedTogetherWithItsParent(): void
    {
        $scope = $this->createScope(5);
        $parent = $this->createMenuUpdate('menu_item_1', 'Home', $scope);
        $parent->setCustom(true);
        $child = $this->createMenuUpdate('my_account', 'My Account', $scope, 'menu_item_1');

        $this->givenScheduled(deletions: [$parent, $child], originalData: [
            spl_object_id($parent) => ['uri' => '/home'],
            spl_object_id($child) => ['uri' => '/my-account'],
        ]);

        $this->flushAndRecord();

        self::assertCount(2, $this->recorded);
        self::assertSame(Audit::ACTION_REMOVE, $this->recorded[0]['entry']->getAction());
        self::assertSame(Audit::ACTION_UPDATE, $this->recorded[1]['entry']->getAction());
    }

    public function testRecordsAStandardItemBroughtBackToItsDefaultAsAChange(): void
    {
        $scope = $this->createScope(5);
        $myUser = $this->createMenuUpdate('my_user', 'My User', $scope);

        $this->givenScheduled(deletions: [$myUser], originalData: [
            spl_object_id($myUser) => ['active' => false],
        ]);

        $this->flushAndRecord();

        $entry = $this->recorded[0]['entry'];
        self::assertSame(Audit::ACTION_UPDATE, $entry->getAction());
        self::assertSame(
            ['active' => ['field' => 'active', 'type' => 'boolean', 'old' => false, 'new' => null]],
            $entry->getChanges()
        );
    }

    public function testFirstCustomizationRecordsOnlyWhatChanged(): void
    {
        $scope = $this->createScope(5);
        $menuUpdate = $this->createMenuUpdate('contact_us', 'Contact', $scope);
        $this->inheritedState->expects(self::any())
            ->method('getInheritedState')
            ->with($menuUpdate)
            ->willReturn([
                'uri' => '/contact',
                'priority' => 3,
                'active' => true,
                'titles' => ['' => 'Contact Us'],
            ]);

        $this->givenScheduled(
            insertions: [$menuUpdate],
            changeSets: [
                spl_object_id($menuUpdate) => [
                    'uri' => [null, '/contact'],
                    'priority' => [null, 3],
                    'active' => [null, true],
                    'titles' => [null, 'Contact'],
                ],
            ]
        );

        $this->flushAndRecord();

        self::assertCount(1, $this->recorded);
        $entry = $this->recorded[0]['entry'];
        self::assertSame(Audit::ACTION_UPDATE, $entry->getAction());
        self::assertSame(
            ['titles' => ['field' => 'titles', 'type' => 'text', 'old' => 'Contact Us', 'new' => 'Contact']],
            $entry->getChanges()
        );
    }

    public function testFirstCustomizationRecordsOnlyTheTranslationThatChanged(): void
    {
        $scope = $this->createScope(5);
        $menuUpdate = $this->createMenuUpdate('profile', 'Edit Profile 2', $scope);
        $this->inheritedState->expects(self::any())
            ->method('getInheritedState')
            ->willReturn([
                'titles' => [
                    '' => 'Edit Profile',
                    'German (Germany)' => 'Profil bearbeiten',
                    'French (France)' => 'Modifier le Profil',
                ],
            ]);

        $this->givenScheduled(insertions: [$menuUpdate], changeSets: [
            spl_object_id($menuUpdate) => [
                'titles' => [null, 'Edit Profile 2'],
                'titles|German (Germany)' => [null, 'Profil bearbeiten'],
                'titles|French (France)' => [null, 'Modifier le Profil'],
            ],
        ]);

        $this->flushAndRecord();

        self::assertSame(
            ['titles' => ['field' => 'titles', 'type' => 'text', 'old' => 'Edit Profile', 'new' => 'Edit Profile 2']],
            $this->recorded[0]['entry']->getChanges()
        );
    }

    public function testTakesThePreviousTitleFromTheMenuWhenTheItemHadNoneOfItsOwn(): void
    {
        $scope = $this->createScope(5);
        $menuUpdate = $this->createMenuUpdate('oro_customer_frontend_customer_user_account', 'My Account 2', $scope);
        $this->inheritedState->expects(self::any())
            ->method('getInheritedState')
            ->willReturn(['titles' => []]);

        $this->givenScheduled(insertions: [$menuUpdate], changeSets: [
            spl_object_id($menuUpdate) => ['titles' => [null, 'My Account 2']],
        ]);

        $this->flushAndRecord();

        self::assertSame(
            ['titles' => ['field' => 'titles', 'type' => 'text', 'old' => 'My Account', 'new' => 'My Account 2']],
            $this->recorded[0]['entry']->getChanges()
        );
    }

    public function testCreatingAnItemIsRecordedAsACreation(): void
    {
        $scope = $this->createScope(5);
        $terms = $this->createMenuUpdate('menu_item_1', 'Terms', $scope);
        $terms->setCustom(true);

        $this->givenScheduled(insertions: [$terms], changeSets: [
            spl_object_id($terms) => [
                'uri' => [null, '/terms'],
                'active' => [null, true],
                'parentKey' => [null, 'oro_customer_frontend_customer_user_account'],
            ],
        ]);

        $this->flushAndRecord();

        $entry = $this->recorded[0]['entry'];
        self::assertSame(Audit::ACTION_CREATE, $entry->getAction());
        self::assertSame(['uri', 'active', 'parentKey'], array_keys($entry->getChanges()));
        self::assertSame(
            ['field' => 'parentKey', 'type' => 'text', 'old' => null, 'new' => 'My Account'],
            $entry->getChanges()['parentKey']
        );
    }

    public function testCustomizingAnItemCreatedByHandOnAnotherLevelIsRecordedAsAChange(): void
    {
        $scope = $this->createScope(5);
        $terms = $this->createMenuUpdate('menu_item_1', 'Terms of use', $scope);
        $terms->setCustom(true);
        $this->inheritedState->expects(self::any())
            ->method('getInheritedState')
            ->willReturn(['titles' => ['' => 'Terms']]);

        $this->givenScheduled(insertions: [$terms], changeSets: [
            spl_object_id($terms) => ['titles' => [null, 'Terms of use']],
        ]);

        $this->flushAndRecord();

        self::assertSame(Audit::ACTION_UPDATE, $this->recorded[0]['entry']->getAction());
    }

    public function testRecordsARemovedItemFromWhatItUsedToBe(): void
    {
        $scope = $this->createScope(5);
        $terms = $this->createMenuUpdate('menu_item_1', 'Terms', $scope);
        $terms->setCustom(true);

        $this->givenScheduled(deletions: [$terms], originalData: [
            spl_object_id($terms) => [
                'uri' => '/terms',
                'priority' => 2,
                'parentKey' => 'oro_customer_frontend_customer_user_account',
                'scope_id' => 5,
            ],
        ]);

        $this->flushAndRecord();

        $entry = $this->recorded[0]['entry'];
        self::assertSame(Audit::ACTION_REMOVE, $entry->getAction());
        self::assertSame(
            [
                'uri' => ['field' => 'uri', 'type' => 'text', 'old' => '/terms', 'new' => null],
                'priority' => ['field' => 'priority', 'type' => 'integer', 'old' => 2, 'new' => null],
                'parentKey' => ['field' => 'parentKey', 'type' => 'text', 'old' => 'My Account', 'new' => null],
            ],
            $entry->getChanges()
        );
    }

    public function testNamesAnItemWithoutItsOwnTitleByTheTitleTheMenuGivesIt(): void
    {
        $scope = $this->createScope(5);
        $item = $this->createMenuUpdate('oro_customer_frontend_customer_user_account', null, $scope);

        $this->givenScheduled(updates: [$item], changeSets: [
            spl_object_id($item) => ['active' => [true, false]],
        ]);

        $this->flushAndRecord();

        self::assertSame(self::MENU . ' / My Account', $this->recorded[0]['entry']->getObjectName());
    }

    public function testShowsTheParentOfAnItemAsAnItemAndNotAsAKey(): void
    {
        $scope = $this->createScope(5);
        $moved = $this->createMenuUpdate('addresses', 'Addresses', $scope, 'home');
        $newParent = $this->createMenuUpdate('home', 'Home', $scope);

        $this->givenScheduled(updates: [$moved, $newParent], changeSets: [
            spl_object_id($moved) => [
                'parentKey' => ['oro_customer_frontend_customer_user_account', 'home'],
            ],
            spl_object_id($newParent) => ['priority' => [1, 2]],
        ]);

        $this->flushAndRecord();

        self::assertSame(
            [
                'parentKey' => [
                    'field' => 'parentKey',
                    'type' => 'text',
                    'old' => 'My Account',
                    'new' => 'Home',
                ],
            ],
            $this->recorded[0]['entry']->getChanges()
        );
    }

    public function testDoesNotRecordAParentThatOnlyChangedHowTheTopLevelIsWritten(): void
    {
        $scope = $this->createScope(5);
        $menuUpdate = $this->createMenuUpdate('menu_item_1', 'Terms', $scope);

        $this->givenScheduled(updates: [$menuUpdate], changeSets: [
            spl_object_id($menuUpdate) => [
                'parentKey' => [self::MENU, null],
                'titles' => ['Terms', 'Terms and Conditions'],
            ],
        ]);

        $this->flushAndRecord();

        self::assertSame(['titles'], array_keys($this->recorded[0]['entry']->getChanges()));
    }

    public function testReadsTheTitleOfAParentThatExistsOnlyAsACustomItem(): void
    {
        $scope = $this->createScope(5);
        $moved = $this->createMenuUpdate('addresses', 'Addresses', $scope, 'menu_item_1');
        $customParent = $this->createMenuUpdate('menu_item_1', 'My Links', $scope);

        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->with(['menu' => self::MENU, 'key' => 'menu_item_1', 'scope' => $scope])
            ->willReturn($customParent);

        $this->givenScheduled(updates: [$moved], changeSets: [
            spl_object_id($moved) => ['parentKey' => [null, 'menu_item_1']],
        ]);

        $this->flushAndRecord();

        self::assertSame(
            ['field' => 'parentKey', 'type' => 'text', 'old' => self::MENU, 'new' => 'My Links'],
            $this->recorded[0]['entry']->getChanges()['parentKey']
        );
    }

    public function testRecordsTheEditedTitleOfAnItemThatDidNotChangeItself(): void
    {
        $scope = $this->createScope(5);
        $menuUpdate = $this->createMenuUpdate('contact_us', 'Contact Us', $scope);
        $title = $menuUpdate->getTitles()->first();

        $this->givenScheduled(updates: [$title]);
        $this->unitOfWork->expects(self::any())
            ->method('getEntityChangeSet')
            ->with($title)
            ->willReturn(['string' => ['Contact Us', 'Contact']]);
        $this->unitOfWork->expects(self::any())
            ->method('getIdentityMap')
            ->willReturn([MenuUpdate::class => ['1' => $menuUpdate]]);

        $this->flushAndRecord();

        self::assertCount(1, $this->recorded);
        self::assertSame(
            ['titles' => ['field' => 'titles', 'type' => 'text', 'old' => 'Contact Us', 'new' => 'Contact']],
            $this->recorded[0]['entry']->getChanges()
        );
    }

    public function testRecordsALocalizedTitleUnderItsLocalization(): void
    {
        $scope = $this->createScope(5);
        $menuUpdate = $this->createMenuUpdate('contact_us', 'Contact Us', $scope);

        $german = new LocalizedFallbackValue();
        $german->setString('Kontaktiere uns');
        $localization = new Localization();
        $localization->setName('German');
        $german->setLocalization($localization);

        $collection = new PersistentCollection(
            $this->createMock(EntityManagerInterface::class),
            LocalizedFallbackValue::class,
            new ArrayCollection([$german])
        );
        $collection->setOwner($menuUpdate, [
            'fieldName' => 'titles',
            'targetEntity' => LocalizedFallbackValue::class,
            'inversedBy' => null,
            'mappedBy' => null,
        ]);

        $this->givenScheduled(collectionUpdates: [$collection]);

        $this->flushAndRecord();

        self::assertSame(['titles|German'], array_keys($this->recorded[0]['entry']->getChanges()));
    }

    public function testSaysWhatTheMenuWasCustomizedForOnALevelOfItsOwn(): void
    {
        $this->levelProvider->expects(self::any())
            ->method('getTargetName')
            ->willReturn('John Doe');

        $scope = $this->createScope(5);
        $menuUpdate = $this->createMenuUpdate('contact_us', 'Contact Us', $scope);
        $this->givenScheduled(updates: [$menuUpdate], changeSets: [
            spl_object_id($menuUpdate) => ['uri' => ['/a', '/b']],
        ]);

        $this->flushAndRecord();

        self::assertSame(
            self::MENU . ' / Contact Us (John Doe)',
            $this->recorded[0]['entry']->getObjectName()
        );
    }

    public function testRecordsNothingUntilTheMenuIsReportedAsChanged(): void
    {
        $scope = $this->createScope(5);
        $synthetic = $this->createMenuUpdate('web_catalog_node_1', 'About Us', $scope);

        $this->givenScheduled(insertions: [$synthetic], changeSets: [
            spl_object_id($synthetic) => ['uri' => [null, '/about-us']],
        ]);

        $this->collector->onFlush($this->eventArgs);

        self::assertSame([], $this->recorded);
    }

    public function testForgetsWhatItCollectedOnceRecorded(): void
    {
        $scope = $this->createScope(5);
        $menuUpdate = $this->createMenuUpdate('contact_us', 'Contact Us', $scope);

        $this->givenScheduled(updates: [$menuUpdate], changeSets: [
            spl_object_id($menuUpdate) => ['uri' => ['/a', '/b']],
        ]);

        $this->flushAndRecord();
        $this->listener->onMenuUpdateChange();

        self::assertCount(1, $this->recorded);
    }

    public function testCollectsNothingWhenTheAuditIsDisabled(): void
    {
        $recorder = $this->createMock(AuditEntryRecorder::class);
        $recorder->expects(self::any())
            ->method('isEnabled')
            ->willReturn(false);
        $menuUpdate = $this->createMenuUpdate('contact_us', 'Contact Us', $this->createScope(5));
        $this->givenScheduled(updates: [$menuUpdate], changeSets: [
            spl_object_id($menuUpdate) => ['uri' => ['/old', '/new']],
        ]);

        $collector = new MenuUpdateChangeCollector($recorder, MenuUpdate::class);
        $collector->onFlush($this->eventArgs);

        self::assertSame([], $collector->release());
    }

    public function testDoesNotAskWhetherTheAuditIsEnabledWithoutAChangeOfItsOwn(): void
    {
        $recorder = $this->createMock(AuditEntryRecorder::class);
        $recorder->expects(self::never())
            ->method('isEnabled');
        $this->givenScheduled(updates: [new \stdClass()], deletions: [new \stdClass()]);

        $collector = new MenuUpdateChangeCollector($recorder, MenuUpdate::class);
        $collector->onFlush($this->eventArgs);

        self::assertSame([], $collector->release());
    }

    public function testIgnoresMenuUpdatesOfAnotherKindOfMenu(): void
    {
        $collector = new MenuUpdateChangeCollector($this->auditEntryRecorder, 'Acme\Bundle\DemoBundle\MenuUpdate');
        $menuUpdate = $this->createMenuUpdate('contact_us', 'Contact Us', $this->createScope(5));

        $this->givenScheduled(updates: [$menuUpdate], changeSets: [
            spl_object_id($menuUpdate) => ['uri' => ['/a', '/b']],
        ]);

        $collector->onFlush($this->eventArgs);

        self::assertSame([], $collector->release());
    }

    private function flushAndRecord(): void
    {
        $this->collector->onFlush($this->eventArgs);
        $this->listener->onMenuUpdateChange();
    }

    private function createItemNameProvider(): MenuItemNameProvider
    {
        $this->repository = $this->createMock(ObjectRepository::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $configurationProvider = $this->createMock(ConfigurationProvider::class);
        $configurationProvider->expects(self::any())
            ->method('getMenuItems')
            ->willReturn(['oro_customer_frontend_customer_user_account' => ['label' => 'oro.customer.menu.label']]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn (string $key): string => match ($key) {
                'oro.customer.menu.label' => 'My Account',
                default => $key,
            });

        return new MenuItemNameProvider($doctrine, $configurationProvider, $translator, MenuUpdate::class);
    }

    private function givenScheduled(
        array $insertions = [],
        array $updates = [],
        array $deletions = [],
        array $collectionUpdates = [],
        array $changeSets = [],
        array $originalData = []
    ): void {
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityInsertions')
            ->willReturn($insertions);
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityUpdates')
            ->willReturn($updates);
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledEntityDeletions')
            ->willReturn($deletions);
        $this->unitOfWork->expects(self::any())
            ->method('getScheduledCollectionUpdates')
            ->willReturn($collectionUpdates);
        if ($changeSets) {
            $this->unitOfWork->expects(self::any())
                ->method('getEntityChangeSet')
                ->willReturnCallback(
                    static fn (object $entity): array => $changeSets[spl_object_id($entity)] ?? []
                );
        }
        if ($originalData) {
            $this->unitOfWork->expects(self::any())
                ->method('getOriginalEntityData')
                ->willReturnCallback(
                    static fn (object $entity): array => $originalData[spl_object_id($entity)] ?? []
                );
        }
    }

    private function createScope(int $id): Scope
    {
        $scope = new Scope();
        ReflectionUtil::setId($scope, $id);

        return $scope;
    }

    private function createMenuUpdate(
        string $key,
        ?string $title,
        Scope $scope,
        ?string $parentKey = null
    ): MenuUpdate {
        $menuUpdate = new MenuUpdate();
        $menuUpdate->setMenu(self::MENU);
        $menuUpdate->setKey($key);
        $menuUpdate->setScope($scope);
        $menuUpdate->setParentKey($parentKey);
        if (null !== $title) {
            $menuUpdate->addTitle((new LocalizedFallbackValue())->setString($title));
        }

        return $menuUpdate;
    }
}
