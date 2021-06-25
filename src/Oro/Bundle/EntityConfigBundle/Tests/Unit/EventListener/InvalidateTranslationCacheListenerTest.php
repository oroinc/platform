<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\EventListener\InvalidateTranslationCacheListener;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;

class InvalidateTranslationCacheListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var InvalidateTranslationCacheListener */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);

        $this->listener = new InvalidateTranslationCacheListener($this->registry);
    }

    public function testOnInvalidateTranslationCacheWhenNoClearableCacheProvider()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(AbstractEnumValue::class)
            ->willReturn($entityManager);

        $configuration = new Configuration();
        $entityManager->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $cacheProvider = $this->getMockBuilder(Cache::class)
            ->onlyMethods(['fetch', 'contains', 'save', 'delete', 'getStats'])
            ->addMethods(['deleteAll'])
            ->getMock();
        $configuration->setQueryCacheImpl($cacheProvider);
        $cacheProvider->expects($this->never())
            ->method('deleteAll');

        $this->listener->onInvalidateTranslationCache();
    }

    public function testOnInvalidateTranslationCache()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(AbstractEnumValue::class)
            ->willReturn($entityManager);

        $configuration = new Configuration();
        $entityManager->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $cacheProvider = $this->createMock(CacheProvider::class);
        $configuration->setQueryCacheImpl($cacheProvider);
        $cacheProvider->expects($this->once())
            ->method('deleteAll');

        $this->listener->onInvalidateTranslationCache();
    }
}
