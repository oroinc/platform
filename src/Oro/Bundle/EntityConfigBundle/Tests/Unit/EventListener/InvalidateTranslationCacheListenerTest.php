<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\EventListener;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\EventListener\InvalidateTranslationCacheListener;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Symfony\Component\Cache\Adapter\AbstractAdapter;

class InvalidateTranslationCacheListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var InvalidateTranslationCacheListener */
    private $listener;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->listener = new InvalidateTranslationCacheListener($this->doctrine);
    }

    public function testOnInvalidateDynamicTranslationCacheWhenNoClearableCacheProvider(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(AbstractEnumValue::class)
            ->willReturn($entityManager);

        $configuration = new Configuration();
        $configuration->setQueryCache($this->createMock(AbstractAdapter::class));

        $entityManager->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $this->listener->onInvalidateDynamicTranslationCache();
    }

    public function testOnInvalidateDynamicTranslationCache()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(AbstractEnumValue::class)
            ->willReturn($entityManager);

        $configuration = new Configuration();
        $entityManager->expects($this->once())
            ->method('getConfiguration')
            ->willReturn($configuration);

        $cacheProvider = $this->createMock(AbstractAdapter::class);
        $configuration->setQueryCache($cacheProvider);
        $cacheProvider->expects($this->once())
            ->method('clear');

        $this->listener->onInvalidateDynamicTranslationCache();
    }
}
