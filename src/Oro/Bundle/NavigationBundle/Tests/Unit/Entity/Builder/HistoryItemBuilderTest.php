<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Builder;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\NavigationBundle\Entity\Builder\HistoryItemBuilder;
use Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HistoryItemBuilderTest extends TestCase
{
    private EntityManager&MockObject $em;
    private ItemFactory&MockObject $factory;
    private HistoryItemBuilder $builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->factory = $this->createMock(ItemFactory::class);

        $this->builder = new HistoryItemBuilder($this->em, $this->factory);
    }

    public function testBuildItem(): void
    {
        $itemBuilder = $this->builder;
        $itemBuilder->setClassName(NavigationHistoryItem::class);

        $user = $this->createMock(User::class);
        $params = [
            'title' => 'kldfjs;jasf',
            'url' => 'some url',
            'user' => $user,
        ];

        $item = $itemBuilder->buildItem($params);

        $this->assertInstanceOf(NavigationHistoryItem::class, $item);
        $this->assertEquals($params['title'], $item->getTitle());
        $this->assertEquals($params['url'], $item->getUrl());
        $this->assertEquals($user, $item->getUser());
        $this->assertInstanceOf(User::class, $item->getUser());
    }

    public function testFindItem(): void
    {
        $itemBuilder = $this->builder;
        $itemBuilder->setClassName(NavigationHistoryItem::class);

        $itemId = 1;
        $this->em->expects($this->once())
            ->method('find')
            ->with(NavigationHistoryItem::class, $itemId)
            ->willReturn(new NavigationHistoryItem());

        $item = $itemBuilder->findItem($itemId);
        $this->assertInstanceOf(NavigationHistoryItem::class, $item);
    }
}
