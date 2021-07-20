<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Manager;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\Repository\LocalizationRepository;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalizationManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LocalizationRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var LocalizationManager */
    private $manager;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    /** @var Localization[]|array */
    private $entities = [];

    protected function setUp(): void
    {
        $this->repository = $this->createMock(LocalizationRepository::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->cacheProvider = $this->createMock(CacheProvider::class);

        /** @var Localization[] $entities */
        $this->entities = [
            1 => $this->getEntity(Localization::class, ['id' => 1]),
            3 => $this->getEntity(Localization::class, ['id' => 3]),
            2 => $this->getEntity(Localization::class, ['id' => 2]),
        ];

        $this->manager = new LocalizationManager(
            $this->doctrineHelper,
            $this->configManager,
            $this->cacheProvider
        );
    }

    public function testGetLocalization()
    {
        /** @var Localization $entity */
        $entity = $this->getEntity(Localization::class, ['id' => 1]);

        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertCacheReads(false);
        $this->assertRepositoryCalls($entity);

        $result = $this->manager->getLocalization($entity->getId());

        $this->assertEquals($entity, $result);
    }

    public function testGetLocalizationWithCache()
    {
        /** @var Localization $entity */
        $entity = $this->getEntity(Localization::class, ['id' => 1]);

        $this->assertCacheReads([1 => $entity]);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->once())
            ->method('merge')
            ->with($entity);
        $uow->expects($this->once())
            ->method('markReadOnly')
            ->with($entity);
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(Localization::class)
            ->willReturn($entityManager);

        $result = $this->manager->getLocalization($entity->getId());

        $this->assertEquals($entity, $result);
    }

    public function testGetLocalizations()
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertRepositoryCalls(null, $this->entities);
        $this->assertCacheReads(false);

        $result = $this->manager->getLocalizations();

        $this->assertEquals($this->entities, $result);
    }

    public function testGetLocalizationsCached()
    {
        $this->assertGetEntityRepositoryForClassIsNotCalled();
        $this->assertCacheReads($this->entities);

        $uow = $this->createMock(UnitOfWork::class);
        $uow->expects($this->exactly(3))
            ->method('merge')
            ->withConsecutive([$this->entities[1]], [$this->entities[3]], [$this->entities[2]]);

        $uow->expects($this->exactly(3))
            ->method('markReadOnly')
            ->withConsecutive([$this->entities[1]], [$this->entities[3]], [$this->entities[2]]);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->once())
            ->method('getUnitOfWork')
            ->willReturn($uow);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityManager')
            ->with(Localization::class)
            ->willReturn($entityManager);

        $result = $this->manager->getLocalizations();

        $this->assertEquals($this->entities, $result);
    }

    public function testGetLocalizationsForcedCacheDisabled()
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertRepositoryCalls(null, $this->entities);

        //Cache should not be accessed at all
        $this->cacheProvider->expects($this->never())
            ->method('fetch');
        $this->cacheProvider->expects($this->never())
            ->method('save');

        $result = $this->manager->getLocalizations(null, false);

        $this->assertEquals($this->entities, $result);
    }

    public function testGetLocalizationsByIds()
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertRepositoryCalls(null, $this->entities);
        $this->assertCacheReads(false);

        /** @var Localization[] $entities */
        $entities = [
            1 => $this->getEntity(Localization::class, ['id' => 1]),
            3 => $this->getEntity(Localization::class, ['id' => 3]),
        ];

        $ids = [1, '3'];

        $result = $this->manager->getLocalizations((array)$ids);

        $this->assertEquals($entities, $result);
    }

    public function testGetDefaultLocalization()
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertCacheReads(false);

        $localization1 = $this->getEntity(Localization::class, ['id' => 1]);
        $localization2 = $this->getEntity(Localization::class, ['id' => 2]);

        $this->repository->expects($this->never())->method('find');
        $this->repository->expects($this->once())->method('findAllIndexedById')
            ->willReturn([1 => $localization1, 2 => $localization2]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn('1');

        $this->assertSame($localization1, $this->manager->getDefaultLocalization());
    }

    public function testGetFirstLocalizationWhenNoDefaultLocalizationExist()
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertCacheReads(false);

        $localization1 = $this->getEntity(Localization::class, ['id' => 1]);
        $localization2 = $this->getEntity(Localization::class, ['id' => 2]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn(false);

        $this->repository->expects($this->once())->method('findAllIndexedById')
            ->willReturn([1 => $localization1, 2 => $localization2]);

        $this->assertSame($localization1, $this->manager->getDefaultLocalization());
    }

    public function testDefaultLocalizationIsNullWhenNoLocalizationsExist()
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertCacheReads(false);
        $this->repository->expects($this->once())->method('findAllIndexedById')->willReturn([]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn(false);

        $this->assertNull($this->manager->getDefaultLocalization());
    }

    public function testGetFirstLocalizationWhenUnknownDefaultLocalizationReturnedFromConfig()
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertCacheReads(false);

        $localization1 = $this->getEntity(Localization::class, ['id' => 1]);
        $localization2 = $this->getEntity(Localization::class, ['id' => 2]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn('13');

        $this->repository->expects($this->once())->method('findAllIndexedById')
            ->willReturn([1 => $localization1, 2 => $localization2]);

        $this->assertSame($localization1, $this->manager->getDefaultLocalization());
    }

    public function testWarmUpCache()
    {
        $this->doctrineHelper->expects($this->exactly(1))
            ->method('getEntityRepositoryForClass')
            ->with(Localization::class)
            ->willReturn($this->repository);

        $this->cacheProvider->expects($this->exactly(2))
            ->method('fetch')
            ->will($this->returnValueMap([
                ['ORO_LOCALE_LOCALIZATION_DATA', false],
                ['ORO_LOCALE_LOCALIZATION_DATA_SIMPLE', []]
            ]));

        $this->repository->expects($this->once())
            ->method('findAllIndexedById')
            ->willReturn($this->entities);

        $this->manager->warmUpCache();
    }

    /**
     * @param Localization   $entity
     * @param Localization[] $entities
     */
    protected function assertRepositoryCalls(Localization $entity = null, array $entities = [])
    {
        if (count($entities) > 0) {
            $this->repository->expects($this->once())->method('findAllIndexedById')->willReturn($entities);
        }

        if ($entity) {
            $this->repository->expects($this->once())->method('find')->willReturn($entity);
        }
    }

    protected function assertCacheReads($results)
    {
        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->willReturn($results);
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
