<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalizationManagerTest extends TestCase
{
    private LocalizationRepository&MockObject $repository;
    private DoctrineHelper&MockObject $doctrineHelper;
    private LocalizationManager $manager;
    private ConfigManager&MockObject $configManager;
    private CacheItemPoolInterface&MockObject $cacheProvider;
    private CacheItemInterface&MockObject $cacheItem;

    /** @var Localization[] */
    private array $entities = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->repository = $this->createMock(LocalizationRepository::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->cacheProvider = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);

        $this->entities = [
            1 => $this->getLocalization(1),
            3 => $this->getLocalization(3),
            2 => $this->getLocalization(2)
        ];

        $this->manager = new LocalizationManager(
            $this->doctrineHelper,
            $this->configManager,
            $this->cacheProvider
        );
    }

    private function getLocalization(int $id): Localization
    {
        $localization = new Localization();
        ReflectionUtil::setId($localization, $id);

        return $localization;
    }

    public function testGetLocalization(): void
    {
        $entity = $this->getLocalization(1);

        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertCacheReads(false);
        $this->assertRepositoryCalls($entity);

        $result = $this->manager->getLocalization($entity->getId());

        $this->assertEquals($entity, $result);
    }

    public function testGetLocalizationWithCache(): void
    {
        $entity = $this->getLocalization(1);

        $this->assertCacheReads([1 => $entity]);
        $result = $this->manager->getLocalization($entity->getId());

        $this->assertEquals($entity, $result);
    }

    public function testGetLocalizations(): void
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertRepositoryCalls(null, $this->entities);
        $this->assertCacheReads(false, true);
        $this->cacheProvider->expects($this->atLeastOnce())
            ->method('saveDeferred');
        $this->cacheProvider->expects($this->atLeastOnce())
            ->method('commit');

        $result = $this->manager->getLocalizations();

        $this->assertEquals($this->entities, $result);
    }

    public function testGetLocalizationsCached(): void
    {
        $this->assertGetEntityRepositoryForClassIsNotCalled();
        $this->assertCacheReads($this->entities);

        $result = $this->manager->getLocalizations();

        $this->assertEquals($this->entities, $result);
    }

    public function testGetLocalizationsForcedCacheDisabled(): void
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertRepositoryCalls(null, $this->entities);

        //Cache should not be accessed at all
        $this->cacheProvider->expects($this->never())
            ->method('getItem');
        $this->cacheProvider->expects($this->never())
            ->method('save');

        $result = $this->manager->getLocalizations(null, false);

        $this->assertEquals($this->entities, $result);
    }

    public function testGetLocalizationsByIds(): void
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertRepositoryCalls(null, $this->entities);
        $this->assertCacheReads(false, true);

        $entities = [
            1 => $this->getLocalization(1),
            3 => $this->getLocalization(3)
        ];

        $ids = [1, '3'];

        $result = $this->manager->getLocalizations($ids);

        $this->assertEquals($entities, $result);
    }

    public function testGetDefaultLocalization(): void
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertCacheReads(false, true);

        $localization1 = $this->getLocalization(1);
        $localization2 = $this->getLocalization(2);

        $this->repository->expects($this->never())
            ->method('find');
        $this->repository->expects($this->once())
            ->method('findAllIndexedById')
            ->willReturn([1 => $localization1, 2 => $localization2]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn('1');

        $this->assertSame($localization1, $this->manager->getDefaultLocalization());
    }

    public function testGetFirstLocalizationWhenNoDefaultLocalizationExist(): void
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertCacheReads(false, true);

        $localization1 = $this->getLocalization(1);
        $localization2 = $this->getLocalization(2);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn(false);

        $this->repository->expects($this->once())
            ->method('findAllIndexedById')
            ->willReturn([1 => $localization1, 2 => $localization2]);

        $this->assertSame($localization1, $this->manager->getDefaultLocalization());
    }

    public function testDefaultLocalizationIsNullWhenNoLocalizationsExist(): void
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertCacheReads(false);
        $this->repository->expects($this->once())
            ->method('findAllIndexedById')
            ->willReturn([]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn(false);

        $this->assertNull($this->manager->getDefaultLocalization());
    }

    public function testGetFirstLocalizationWhenUnknownDefaultLocalizationReturnedFromConfig(): void
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertCacheReads(false, true);

        $localization1 = $this->getLocalization(1);
        $localization2 = $this->getLocalization(2);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn('13');

        $this->repository->expects($this->once())
            ->method('findAllIndexedById')
            ->willReturn([1 => $localization1, 2 => $localization2]);

        $this->assertSame($localization1, $this->manager->getDefaultLocalization());
    }

    /**
     * @param Localization|null $entity
     * @param Localization[] $entities
     */
    private function assertRepositoryCalls(?Localization $entity = null, array $entities = [])
    {
        if (count($entities) > 0) {
            $this->repository->expects($this->once())
                ->method('findAllIndexedById')
                ->willReturn($entities);
        }

        if ($entity) {
            $this->repository->expects($this->once())
                ->method('find')
                ->willReturn($entity);
        }
    }

    private function assertCacheReads($results, $multiple = false)
    {
        $this->cacheProvider->expects($multiple ? $this->atLeastOnce() : $this->once())
            ->method('getItem')
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn((bool) $results);
        if ($results !== false) {
            $this->cacheItem->expects($this->once())
                ->method('get')
                ->willReturn($results);
        }
    }

    private function assertGetEntityRepositoryForClassIsCalled()
    {
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Localization::class)
            ->willReturn($this->repository);
    }

    private function assertGetEntityRepositoryForClassIsNotCalled()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepositoryForClass');
    }
}
