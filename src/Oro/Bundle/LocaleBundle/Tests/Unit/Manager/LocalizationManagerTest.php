<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Manager;

use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class LocalizationManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    protected $repository;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var LocalizationManager */
    protected $manager;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $configManager;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $cacheProvider;

    /** @var Localization[]|array */
    protected $entities = [];

    public function setUp()
    {
        $this->repository = $this->getMockBuilder(ObjectRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cacheProvider = $this->getMockBuilder(CacheProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

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

    public function tearDown()
    {
        unset(
            $this->repository,
            $this->manager,
            $this->configManager,
            $this->entities
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
        $this->repository->expects($this->once())->method('findBy')
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

        $this->repository->expects($this->once())->method('findBy')
            ->willReturn([1 => $localization1, 2 => $localization2]);

        $this->assertSame($localization1, $this->manager->getDefaultLocalization());
    }

    public function testDefaultLocalizationIsNullWhenNoLocalizationsExist()
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertCacheReads(false);
        $this->repository->expects($this->once())->method('findBy')->willReturn([]);

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

        $this->repository->expects($this->once())->method('findBy')
            ->willReturn([1 => $localization1, 2 => $localization2]);

        $this->assertSame($localization1, $this->manager->getDefaultLocalization());
    }

    public function testWarmUpCache()
    {
        $this->assertGetEntityRepositoryForClassIsCalled();
        $this->assertCacheReads(false);
        $this->repository->expects($this->once())
            ->method('findBy')
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
            $this->repository->expects($this->once())->method('findBy')->willReturn($entities);
        }

        if ($entity) {
            $this->repository->expects($this->once())->method('find')->willReturn($entity);
        }
    }

    /**
     * @param $results
     */
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
