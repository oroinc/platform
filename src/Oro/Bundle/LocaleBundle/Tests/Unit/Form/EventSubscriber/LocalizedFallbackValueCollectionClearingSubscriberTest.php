<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Form\EventSubscriber;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\EventSubscriber\LocalizedFallbackValueCollectionClearingSubscriber;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Stub\LocalizationStub;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Component\Form\Event\SubmitEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalizedFallbackValueCollectionClearingSubscriberTest extends \PHPUnit\Framework\TestCase
{
    private LocalizedFallbackValueCollectionClearingSubscriber $subscriber;

    private EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    protected function setUp(): void
    {
        $this->subscriber = new LocalizedFallbackValueCollectionClearingSubscriber();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->entityManager
            ->expects(self::any())
            ->method('getUnitOfWork')
            ->willReturn($this->createMock(UnitOfWork::class));
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'onPreSetData',
                FormEvents::SUBMIT => 'onSubmit',
            ],
            LocalizedFallbackValueCollectionClearingSubscriber::getSubscribedEvents()
        );
    }

    public function testOnPreSetDataWhenNotCollection(): void
    {
        $event = new PreSetDataEvent(
            $this->createMock(FormInterface::class),
            [1, 2, 3]
        );

        $getSnapshots = \Closure::bind(
            fn () => $this->snapshots,
            $this->subscriber,
            LocalizedFallbackValueCollectionClearingSubscriber::class
        );
        self::assertEquals([], $getSnapshots());

        $this->subscriber->onPreSetData($event);

        self::assertEquals([], $getSnapshots());
    }

    public function testOnPreSetDataWhenOriginallyNotEmptyPersistentCollection(): void
    {
        $titles = new PersistentCollection(
            $this->createMock(EntityManagerInterface::class),
            new ClassMetadata(MenuUpdate::class),
            new ArrayCollection([(new LocalizedFallbackValue())->setString('sample1')])
        );
        $titles->takeSnapshot();

        $event = new PreSetDataEvent($this->createMock(FormInterface::class), $titles);

        $getSnapshots = \Closure::bind(
            fn () => $this->snapshots,
            $this->subscriber,
            LocalizedFallbackValueCollectionClearingSubscriber::class
        );
        self::assertEquals([], $getSnapshots());

        $this->subscriber->onPreSetData($event);

        self::assertEquals([], $getSnapshots());
    }

    public function testOnPreSetDataWhenOriginallyEmptyPersistentCollection(): void
    {
        $titles = new PersistentCollection(
            $this->entityManager,
            new ClassMetadata(MenuUpdate::class),
            new ArrayCollection()
        );
        $titles->takeSnapshot();
        $titles->add((new LocalizedFallbackValue())->setString('sample1'));
        $titles->add((new LocalizedFallbackValue())->setString('sample2')->setLocalization(new LocalizationStub(42)));

        $form = $this->createMock(FormInterface::class);
        $event = new PreSetDataEvent($form, $titles);

        $getSnapshots = \Closure::bind(
            fn () => $this->snapshots,
            $this->subscriber,
            LocalizedFallbackValueCollectionClearingSubscriber::class
        );
        self::assertEquals([], $getSnapshots());

        $this->subscriber->onPreSetData($event);

        self::assertEquals(
            [spl_object_hash($form) => [null => $titles[0], 42 => $titles[1]]],
            $getSnapshots()
        );
    }

    public function testOnPreSetDataWhenNotPersistentCollection(): void
    {
        $titles = new ArrayCollection(
            [
                (new LocalizedFallbackValue())->setString('sample1'),
                (new LocalizedFallbackValue())->setString('sample2')->setLocalization(new LocalizationStub(42))
            ]
        );

        $form = $this->createMock(FormInterface::class);
        $event = new PreSetDataEvent($form, $titles);

        $getSnapshots = \Closure::bind(
            fn () => $this->snapshots,
            $this->subscriber,
            LocalizedFallbackValueCollectionClearingSubscriber::class
        );
        self::assertEquals([], $getSnapshots());

        $this->subscriber->onPreSetData($event);

        self::assertEquals(
            [spl_object_hash($form) => [null => $titles[0], 42 => $titles[1]]],
            $getSnapshots()
        );
    }

    public function testOnSubmitWhenNotCollection(): void
    {
        $event = new SubmitEvent(
            $this->createMock(FormInterface::class),
            (object)['key' => 'sample_key', 'titles' => [1, 2, 3]]
        );

        $this->subscriber->onSubmit($event);
    }

    public function testOnSubmitWhenNoSnapshot(): void
    {
        $titles = new ArrayCollection(
            [
                (new LocalizedFallbackValue())->setString('sample1'),
                (new LocalizedFallbackValue())->setString('sample2')->setLocalization(new LocalizationStub(42))
            ]
        );

        $event = new SubmitEvent($this->createMock(FormInterface::class), $titles);

        $this->subscriber->onSubmit($event);

        self::assertCount(2, $titles);
    }

    public function testOnSubmitWhenPersistentCollectionAndNoChanges(): void
    {
        $titles = new PersistentCollection(
            $this->entityManager,
            new ClassMetadata(MenuUpdate::class),
            new ArrayCollection()
        );
        $titles->takeSnapshot();
        // Needed to make PersistentCollection::clear() work.
        $titles->setOwner(
            new \stdClass(),
            [
                'inversedBy' => 'titles',
                'type' => ClassMetadata::ONE_TO_MANY,
                'isOwningSide' => false,
                'orphanRemoval' => false,
            ]
        );

        $title1 = (new LocalizedFallbackValue())->setString('sample1');
        $titles->add($title1);
        $title2 = (new LocalizedFallbackValue())->setString('sample2')->setLocalization(new LocalizationStub(42));
        $titles->add($title2);

        $form = $this->createMock(FormInterface::class);
        $this->subscriber->onPreSetData(new PreSetDataEvent($form, $titles));

        $this->subscriber->onSubmit(new SubmitEvent($form, $titles));

        self::assertCount(0, $titles);
    }

    public function testOnSubmitWhenArrayCollectionAndNoChanges(): void
    {
        $title1 = (new LocalizedFallbackValue())->setString('sample1');
        $title2 = (new LocalizedFallbackValue())->setString('sample2')->setLocalization(new LocalizationStub(42));
        $titles = new ArrayCollection([$title1, $title2]);

        $form = $this->createMock(FormInterface::class);

        $this->subscriber->onPreSetData(new PreSetDataEvent($form, $titles));

        $this->subscriber->onSubmit(new SubmitEvent($form, $titles));

        self::assertCount(0, $titles);
    }

    public function testOnSubmitWhenPersistentCollectionAndHasChanges(): void
    {
        $titles = new PersistentCollection(
            $this->entityManager,
            new ClassMetadata(MenuUpdate::class),
            new ArrayCollection()
        );
        $titles->takeSnapshot();
        $title1 = (new LocalizedFallbackValue())->setString('sample1');
        $titles->add($title1);
        $title2 = (new LocalizedFallbackValue())->setString('sample2')->setLocalization(new LocalizationStub(42));
        $titles->add($title2);

        $form = $this->createMock(FormInterface::class);

        $this->subscriber->onPreSetData(new PreSetDataEvent($form, $titles));

        $title1->setString('sample1-updated');

        $this->subscriber->onSubmit(new SubmitEvent($form, $titles));

        self::assertEquals([$title1, $title2], $titles->toArray());
    }

    public function testOnSubmitWhenArrayCollectionAndHasChanges(): void
    {
        $title1 = (new LocalizedFallbackValue())->setString('sample1');
        $title2 = (new LocalizedFallbackValue())->setString('sample2')->setLocalization(new LocalizationStub(42));
        $titles = new ArrayCollection([$title1, $title2]);

        $form = $this->createMock(FormInterface::class);

        $this->subscriber->onPreSetData(new PreSetDataEvent($form, $titles));

        $title1->setString('sample1-updated');

        $this->subscriber->onSubmit(new SubmitEvent($form, $titles));

        self::assertEquals([$title1, $title2], $titles->toArray());
    }

    public function testOnSubmitWhenPersistentCollectionAndFallbackChanged(): void
    {
        $titles = new PersistentCollection(
            $this->entityManager,
            new ClassMetadata(MenuUpdate::class),
            new ArrayCollection()
        );
        $titles->takeSnapshot();
        $title1 = (new LocalizedFallbackValue())->setString('sample1');
        $titles->add($title1);
        $title2 = (new LocalizedFallbackValue())
            ->setFallback(FallbackType::SYSTEM)
            ->setLocalization(new LocalizationStub(42));
        $titles->add($title2);

        $form = $this->createMock(FormInterface::class);

        $this->subscriber->onPreSetData(new PreSetDataEvent($form, $titles));

        $title2->setFallback(FallbackType::PARENT_LOCALIZATION);

        $this->subscriber->onSubmit(new SubmitEvent($form, $titles));

        self::assertEquals([$title1, $title2], $titles->toArray());
    }

    public function testOnSubmitWhenArrayCollectionAndFallbackChanged(): void
    {
        $title1 = (new LocalizedFallbackValue())->setString('sample1');
        $title2 = (new LocalizedFallbackValue())
            ->setFallback(FallbackType::SYSTEM)
            ->setLocalization(new LocalizationStub(42));
        $titles = new ArrayCollection([$title1, $title2]);

        $form = $this->createMock(FormInterface::class);

        $this->subscriber->onPreSetData(new PreSetDataEvent($form, $titles));

        $title2
            ->setFallback(FallbackType::NONE)
            ->setString('sample-custom');

        $this->subscriber->onSubmit(new SubmitEvent($form, $titles));

        self::assertEquals([$title1, $title2], $titles->toArray());
    }
}
