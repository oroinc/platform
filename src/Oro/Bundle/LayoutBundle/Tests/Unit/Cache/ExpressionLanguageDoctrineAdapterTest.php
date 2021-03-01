<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Cache;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\LayoutBundle\Cache\ExpressionLanguageDoctrineAdapter;
use Symfony\Component\Cache\CacheItem;

class ExpressionLanguageDoctrineAdapterTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var ExpressionLanguageDoctrineAdapter */
    private $adapter;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(CacheProvider::class);

        $this->adapter = new ExpressionLanguageDoctrineAdapter($this->provider, 'test');
    }

    public function testGetItem()
    {
        $key = 'test_item';
        $expectedId = 'orv1thlk24gwoo0k8o0cs8go382qua26l8owcssk04gokso48oooscs';
        $data = 'data';

        $this->provider->expects(self::once())
            ->method('fetchMultiple')
            ->with([$expectedId])
            ->willReturn([$key => $data]);

        $result = $this->adapter->getItem($key);

        self::assertEquals($key, $result->getKey());
        self::assertEquals($data, $result->get());
    }

    public function testGetItems()
    {
        $key = 'test_item';
        $expectedId = 'orv1thlk24gwoo0k8o0cs8go382qua26l8owcssk04gokso48oooscs';
        $data = 'data';

        $this->provider->expects(self::once())
            ->method('fetchMultiple')
            ->with([$expectedId])
            ->willReturn([$key => $data]);

        $result = $this->adapter->getItems([$key]);

        $item = $result->current();
        self::assertEquals($key, $item->getKey());
        self::assertEquals($data, $item->get());
    }

    public function testHasItem()
    {
        $key = 'test_item';
        $expectedId = 'orv1thlk24gwoo0k8o0cs8go382qua26l8owcssk04gokso48oooscs';

        $this->provider->expects(self::once())
            ->method('contains')
            ->with($expectedId)
            ->willReturn(true);

        self::assertTrue($this->adapter->hasItem($key));
    }

    public function testDelete()
    {
        $key = 'test_item';
        $expectedId = 'orv1thlk24gwoo0k8o0cs8go382qua26l8owcssk04gokso48oooscs';

        $this->provider->expects(self::once())
            ->method('delete')
            ->with($expectedId)
            ->willReturn(true);

        self::assertTrue($this->adapter->deleteItem($key));
    }

    public function testSave()
    {
        $data = 'data';
        $key = 'test_item';
        $expectedId = 'orv1thlk24gwoo0k8o0cs8go382qua26l8owcssk04gokso48oooscs';

        $createCacheItem = \Closure::bind(
            static function ($key, $value) {
                $item = new CacheItem();
                $item->key = $key;
                $item->value = $value;

                return $item;
            },
            null,
            CacheItem::class
        );

        $item = $createCacheItem($key, $data);
        $this->provider->expects(self::once())
            ->method('saveMultiple')
            ->with([$expectedId => $data], 0)
            ->willReturn(true);

        self::assertTrue($this->adapter->save($item));
    }
}
