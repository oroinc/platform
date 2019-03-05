<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Builder;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\NavigationBundle\Entity\Builder\PinbarTabBuilder;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\NavigationItemStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\PinbarTabStub;

class PinbarTabBuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var string */
    private $type;

    /** @var PinbarTabBuilder */
    private $builder;

    protected function setUp()
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->type = 'sample-type';

        $this->builder = new PinbarTabBuilder($this->entityManager, $this->type);

        $this->builder
            ->setNavigationItemClassName(NavigationItemStub::class)
            ->setClassName(PinbarTabStub::class);
    }

    public function testBuildItemWhenNotMaximized(): void
    {
        $pinbarTab = $this->builder->buildItem([]);

        self::assertInstanceOf(PinbarTabStub::class, $pinbarTab);
        self::assertInstanceOf(NavigationItemStub::class, $pinbarTab->getItem());
        self::assertEquals($this->type, $pinbarTab->getItem()->getType());
        self::assertNull($pinbarTab->getMaximized());
    }

    public function testBuildItem(): void
    {
        $pinbarTab = $this->builder->buildItem(['maximized' => true]);

        self::assertInstanceOf(PinbarTabStub::class, $pinbarTab);
        self::assertInstanceOf(NavigationItemStub::class, $pinbarTab->getItem());
        self::assertEquals($this->type, $pinbarTab->getItem()->getType());
        self::assertNotNull($pinbarTab->getMaximized());
    }

    public function testFindItem(): void
    {
        $this->entityManager
            ->expects(self::once())
            ->method('find')
            ->with(PinbarTabStub::class, $id = 1)
            ->willReturn($item = new \stdClass());

        self::assertSame($item, $this->builder->findItem($id));
    }
}
