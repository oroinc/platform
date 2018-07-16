<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Builder;

use Oro\Bundle\NavigationBundle\Entity\Builder\HistoryItemBuilder;
use Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem;

class HistoryItemBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var HistoryItemBuilder
     */
    protected $builder;

    /**
     * @var \Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory
     */
    protected $factory;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->factory = $this->createMock('Oro\Bundle\NavigationBundle\Entity\Builder\ItemFactory');
        $this->builder = new HistoryItemBuilder($this->em, $this->factory);
    }

    public function testBuildItem()
    {
        $itemBuilder = $this->builder;
        $itemBuilder->setClassName('Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem');

        $user = $this->createMock('\Oro\Bundle\UserBundle\Entity\User');
        $params = array(
            'title' => 'kldfjs;jasf',
            'url' => 'some url',
            'user' => $user,
        );

        $item = $itemBuilder->buildItem($params);

        $this->assertInstanceOf('Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem', $item);
        $this->assertEquals($params['title'], $item->getTitle());
        $this->assertEquals($params['url'], $item->getUrl());
        $this->assertEquals($user, $item->getUser());
        $this->assertInstanceOf('\Oro\Bundle\UserBundle\Entity\User', $item->getUser());
    }

    public function testFindItem()
    {
        $itemBuilder = $this->builder;
        $itemBuilder->setClassName('Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem');

        $itemId = 1;
        $this->em
            ->expects($this->once())
            ->method('find')
            ->with($this->equalTo('Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem'), $this->equalTo($itemId))
            ->will($this->returnValue(new NavigationHistoryItem()));

        $item = $itemBuilder->findItem($itemId);
        $this->assertInstanceOf('Oro\Bundle\NavigationBundle\Entity\NavigationHistoryItem', $item);
    }
}
