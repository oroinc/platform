<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TranslationBundle\Entity\Repository\TranslationKeyRepository;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Provider\TranslationDomainProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class TranslationDomainProviderTest extends TestCase
{
    private TranslationKeyRepository&MockObject $repository;
    private CacheInterface&MockObject $cache;
    private TranslationDomainProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = $this->createMock(TranslationKeyRepository::class);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(TranslationKey::class)
            ->willReturn($this->repository);

        $this->cache = $this->createMock(CacheInterface::class);

        $this->provider = new TranslationDomainProvider($doctrine, $this->cache);
    }

    public function testGetAvailableDomainsWithoutCache(): void
    {
        $domains = ['domain1', 'domain2'];

        $this->repository->expects(self::once())
            ->method('findAvailableDomains')
            ->willReturn($domains);

        $this->cache->expects(self::once())
            ->method('get')
            ->with('availableDomains')
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        self::assertEquals($domains, $this->provider->getAvailableDomains());
    }

    public function testGetAvailableDomainsWithCache(): void
    {
        $domains = ['domain1', 'domain2'];

        $this->repository->expects(self::never())
            ->method('findAvailableDomains');
        $this->cache->expects(self::once())
            ->method('get')
            ->with('availableDomains')
            ->willReturn($domains);

        self::assertEquals($domains, $this->provider->getAvailableDomains());
    }

    public function testGetAvailableDomainChoicesWithoutCache(): void
    {
        $this->repository->expects(self::once())
            ->method('findAvailableDomains')
            ->willReturn(['domain1', 'domain2']);

        $this->cache->expects(self::once())
            ->method('get')
            ->with('availableDomains')
            ->willReturnCallback(function ($cacheKey, $callback) {
                return $callback($this->createMock(ItemInterface::class));
            });

        self::assertEquals(
            ['domain1' => 'domain1', 'domain2' => 'domain2'],
            $this->provider->getAvailableDomainChoices()
        );
    }

    public function testGetAvailableDomainChoicesWithCache(): void
    {
        $this->repository->expects(self::never())
            ->method('findAvailableDomains');
        $this->cache->expects(self::once())
            ->method('get')
            ->with('availableDomains')
            ->willReturn(['domain1', 'domain2']);

        self::assertEquals(
            ['domain1' => 'domain1', 'domain2' => 'domain2'],
            $this->provider->getAvailableDomainChoices()
        );
    }
}
