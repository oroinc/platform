<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Oro\Bundle\NavigationBundle\Entity\AbstractPinbarTab;
use Oro\Bundle\NavigationBundle\Entity\NavigationItem;
use Oro\Bundle\NavigationBundle\EventListener\PinbarTabSubscriber;
use Oro\Bundle\NavigationBundle\Exception\LogicException;
use Oro\Bundle\NavigationBundle\Provider\PinbarTabTitleProviderInterface;
use Oro\Bundle\NavigationBundle\Utils\PinbarTabUrlNormalizerInterface;

class PinbarTabSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var PinbarTabUrlNormalizerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $pinbarTabUrlNormalizer;

    /** @var PinbarTabTitleProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $pinbarTabTitleProvider;

    /** @var PinbarTabSubscriber */
    private $subscriber;

    protected function setUp()
    {
        $this->pinbarTabUrlNormalizer = $this->createMock(PinbarTabUrlNormalizerInterface::class);
        $this->pinbarTabTitleProvider = $this->createMock(PinbarTabTitleProviderInterface::class);
        $this->subscriber = new PinbarTabSubscriber(
            $this->pinbarTabUrlNormalizer,
            $this->pinbarTabTitleProvider,
            AbstractPinbarTab::class
        );
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertEquals([Events::prePersist], $this->subscriber->getSubscribedEvents());
    }

    public function testPrePersistWhenEntityNotSupported(): void
    {
        $event = $this->createMock(LifecycleEventArgs::class);
        $event
            ->expects(self::once())
            ->method('getEntity')
            ->willReturn($entity = new \stdClass());

        $this->subscriber->prePersist($event);

        self::assertEquals(new \stdClass(), $entity);
    }

    public function testPrePersistWhenEntityNoNavigationItem(): void
    {
        $event = $this->createMock(LifecycleEventArgs::class);
        $event
            ->expects(self::once())
            ->method('getEntity')
            ->willReturn($pinbarTab = $this->createMock(AbstractPinbarTab::class));

        $pinbarTab
            ->expects(self::once())
            ->method('getItem');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('PinbarTab does not contain NavigationItem');

        $this->subscriber->prePersist($event);
    }

    public function testPrePersist(): void
    {
        $event = $this->createMock(LifecycleEventArgs::class);
        $event
            ->expects(self::once())
            ->method('getEntity')
            ->willReturn($pinbarTab = $this->createMock(AbstractPinbarTab::class));

        $pinbarTab
            ->expects(self::once())
            ->method('getItem')
            ->willReturn($navigationItem = $this->createMock(NavigationItem::class));

        $navigationItem
            ->expects(self::once())
            ->method('getUrl')
            ->willReturn($url = 'sample-url');

        $this->pinbarTabUrlNormalizer
            ->expects(self::once())
            ->method('getNormalizedUrl')
            ->with($url)
            ->willReturn($normalizedUrl = 'normalized-sample-url');

        $pinbarTab
            ->expects(self::once())
            ->method('setValues')
            ->with(['url' => $normalizedUrl]);

        $this->pinbarTabTitleProvider
            ->expects(self::once())
            ->method('getTitles')
            ->with($navigationItem, AbstractPinbarTab::class)
            ->willReturn([$title = 'sample-title', $titleShort = 'sample-title-short']);

        $pinbarTab
            ->expects(self::once())
            ->method('setTitle')
            ->with($title);

        $pinbarTab
            ->expects(self::once())
            ->method('setTitleShort')
            ->with($titleShort);

        $this->subscriber->prePersist($event);
    }
}
