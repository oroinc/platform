<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TranslationDomainProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationKeyRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var CacheInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var TranslationDomainProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(TranslationKeyRepository::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects($this->any())
            ->method('getRepository')
            ->with(TranslationKey::class)
            ->willReturn($this->repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(TranslationKey::class)
            ->willReturn($manager);

        $this->cache = $this->createMock(CacheInterface::class);

        $this->provider = new TranslationDomainProvider($registry, $this->cache);
    }

    public function testGetAvailableDomainsWithoutCache()
    {
        $domains = ['domain1' => 'domain1', 'domain2' => 'domain2'];

        $this->repository->expects($this->once())
            ->method('findAvailableDomains')
            ->willReturn($domains);

        $this->cache->expects($this->once())
            ->method('get')
            ->with('availableDomains')
            ->willReturnCallback(function ($cacheKey, $callback) {
                $item = $this->createMock(ItemInterface::class);
                return $callback($item);
            });

        $this->assertEquals($domains, $this->provider->getAvailableDomains());
    }

    public function testGetAvailableDomainsWithCache()
    {
        $domains = ['domain1' => 'domain1', 'domain2' => 'domain2'];

        $this->repository->expects($this->never())
            ->method($this->anything());
        $this->cache->expects($this->once())
            ->method('get')
            ->with('availableDomains')
            ->willReturn($domains);

        $this->assertEquals($domains, $this->provider->getAvailableDomains());
    }

    public function testGetAvailableDomainsForLocales()
    {
        $domains = ['domain1' => 'domain1', 'domain2' => 'domain2'];
        $locales = ['locale1', 'locale2'];

        $this->cache->expects($this->once())
            ->method('get')
            ->willReturn($domains);

        $this->assertEquals(
            [
                ['code' => 'locale1', 'domain' => 'domain1'],
                ['code' => 'locale1', 'domain' => 'domain2'],
                ['code' => 'locale2', 'domain' => 'domain1'],
                ['code' => 'locale2', 'domain' => 'domain2']
            ],
            $this->provider->getAvailableDomainsForLocales($locales)
        );
    }
}
