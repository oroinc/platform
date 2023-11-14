<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\EventListener\InvalidateTranslationCacheListener;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\SecurityBundle\Cache\DoctrineAclCacheProvider;

class InvalidateTranslationCacheListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineAclCacheProvider  */
    protected $queryCacheProvider;

    /**
     * @var InvalidateTranslationCacheListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->queryCacheProvider = $this->createMock(DoctrineAclCacheProvider::class);

        $this->listener = new InvalidateTranslationCacheListener($this->registry);
        $this->listener->setQueryCacheProvider($this->queryCacheProvider);
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

        $this->queryCacheProvider->expects(self::once())
            ->method('clear');

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
        $this->queryCacheProvider->expects(self::once())
            ->method('clear');

        $this->listener->onInvalidateTranslationCache();
    }
}
