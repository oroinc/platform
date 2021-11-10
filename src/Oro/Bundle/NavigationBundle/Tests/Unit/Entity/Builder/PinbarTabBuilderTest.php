<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Builder;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\NavigationBundle\Entity\Builder\PinbarTabBuilder;
use Oro\Bundle\NavigationBundle\Provider\PinbarTabTitleProvider;
use Oro\Bundle\NavigationBundle\Provider\PinbarTabTitleProviderInterface;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\NavigationItemStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\PinbarTabStub;
use Oro\Bundle\NavigationBundle\Utils\PinbarTabUrlNormalizer;

class PinbarTabBuilderTest extends \PHPUnit\Framework\TestCase
{
    private const TYPE = 'sample-type';

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var PinbarTabUrlNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $pinbarTabUrlNormalizer;

    /** @var PinbarTabTitleProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $pinbarTabTitleProvider;

    /** @var PinbarTabBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->pinbarTabUrlNormalizer = $this->createMock(PinbarTabUrlNormalizer::class);
        $this->pinbarTabTitleProvider = $this->createMock(PinbarTabTitleProvider::class);

        $this->builder = new PinbarTabBuilder(
            $this->entityManager,
            $this->pinbarTabUrlNormalizer,
            $this->pinbarTabTitleProvider,
            self::TYPE
        );
        $this->builder->setNavigationItemClassName(NavigationItemStub::class);
        $this->builder->setClassName(PinbarTabStub::class);
    }

    public function testBuildItemWhenNoUrl(): void
    {
        $this->pinbarTabUrlNormalizer->expects(self::never())
            ->method('getNormalizedUrl');

        $this->pinbarTabTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with(self::isInstanceOf(NavigationItemStub::class), PinbarTabStub::class)
            ->willReturn([$title = 'sample-title', $titleShort = 'sample-title-short']);

        $pinbarTab = $this->builder->buildItem(['maximized' => true]);

        self::assertInstanceOf(PinbarTabStub::class, $pinbarTab);
        self::assertInstanceOf(NavigationItemStub::class, $pinbarTab->getItem());
        self::assertEquals(self::TYPE, $pinbarTab->getItem()->getType());
        self::assertEquals($title, $pinbarTab->getTitle());
        self::assertEquals($titleShort, $pinbarTab->getTitleShort());
        self::assertNotNull($pinbarTab->getMaximized());
    }

    public function testBuildItemWhenNotMaximized(): void
    {
        $this->pinbarTabTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with(self::isInstanceOf(NavigationItemStub::class))
            ->willReturn([$title = 'sample-title', $titleShort = 'sample-title-short']);

        $pinbarTab = $this->builder->buildItem([]);

        self::assertInstanceOf(PinbarTabStub::class, $pinbarTab);
        self::assertInstanceOf(NavigationItemStub::class, $pinbarTab->getItem());
        self::assertEquals(self::TYPE, $pinbarTab->getItem()->getType());
        self::assertEquals($title, $pinbarTab->getTitle());
        self::assertEquals($titleShort, $pinbarTab->getTitleShort());
        self::assertNull($pinbarTab->getMaximized());
    }

    public function testBuildItem(): void
    {
        $this->pinbarTabUrlNormalizer->expects(self::once())
            ->method('getNormalizedUrl')
            ->with($url = '/sample-url')
            ->willReturn($urlNormalized = '/sample-url-normalized');

        $this->pinbarTabTitleProvider->expects(self::once())
            ->method('getTitles')
            ->with(self::isInstanceOf(NavigationItemStub::class))
            ->willReturn([$title = 'sample-title', $titleShort = 'sample-title-short']);

        $pinbarTab = $this->builder->buildItem(['url' => $url]);

        self::assertInstanceOf(PinbarTabStub::class, $pinbarTab);
        self::assertInstanceOf(NavigationItemStub::class, $pinbarTab->getItem());
        self::assertEquals(self::TYPE, $pinbarTab->getItem()->getType());
        self::assertEquals($urlNormalized, $pinbarTab->getItem()->getUrl());
        self::assertEquals($title, $pinbarTab->getTitle());
        self::assertEquals($titleShort, $pinbarTab->getTitleShort());
        self::assertNull($pinbarTab->getMaximized());
    }

    public function testFindItem(): void
    {
        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(PinbarTabStub::class, $id = 1)
            ->willReturn($item = new \stdClass());

        self::assertSame($item, $this->builder->findItem($id));
    }
}
