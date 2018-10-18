<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityConfigBundle\EventListener\InvalidateTranslationCacheListener;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Symfony\Bridge\Doctrine\RegistryInterface;

class InvalidateTranslationCacheListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var RegistryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var InvalidateTranslationCacheListener
     */
    private $listener;

    protected function setUp()
    {
        $this->registry = $this->createMock(RegistryInterface::class);
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

        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $cacheProvider */
        $cacheProvider = $this->getMockBuilder(Cache::class)
            ->setMethods([
                'fetch',
                'contains',
                'save',
                'delete',
                'getStats',
                'deleteAll'
            ])
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

        /** @var Cache|\PHPUnit\Framework\MockObject\MockObject $cacheProvider */
        $cacheProvider = $this->createMock(CacheProvider::class);
        $configuration->setQueryCacheImpl($cacheProvider);
        $cacheProvider->expects($this->once())
            ->method('deleteAll');

        $this->listener->onInvalidateTranslationCache();
    }
}
