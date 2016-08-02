<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Extension\CurrentLocalizationExtensionInterface;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

use Oro\Component\Testing\Unit\EntityTrait;

class LocalizationProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var LocalizationProvider */
    protected $provider;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var Localization[]|array */
    protected $entities = [];

    public function setUp()
    {
        $this->repository = $this->getMockBuilder(ObjectRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var Localization[] $entities */
        $this->entities = [
            1 => $this->getEntity(Localization::class, ['id' => 1]),
            3 => $this->getEntity(Localization::class, ['id' => 3]),
            2 => $this->getEntity(Localization::class, ['id' => 2]),
        ];

        $this->provider = new LocalizationProvider($this->repository, $this->configManager);
    }

    public function tearDown()
    {
        unset(
            $this->repository,
            $this->provider,
            $this->configManager,
            $this->entities
        );
    }

    public function testGetLocalization()
    {
        /** @var Localization $entity */
        $entity = $this->getEntity(Localization::class, ['id' => 1]);

        $this->assertRepositoryCalls();

        $result = $this->provider->getLocalization($entity->getId());

        $this->assertEquals($entity, $result);

    }

    public function testGetLocalizations()
    {
        $this->assertRepositoryCalls();

        $result = $this->provider->getLocalizations();

        $this->assertEquals($this->entities, $result);
    }

    public function testGetLocalizationsByIds()
    {
        $this->assertRepositoryCalls();

        /** @var Localization[] $entities */
        $entities = [
            1 => $this->getEntity(Localization::class, ['id' => 1]),
            3 => $this->getEntity(Localization::class, ['id' => 3]),
        ];

        $ids = [1, 3];

        $result = $this->provider->getLocalizations((array)$ids);

        $this->assertEquals($entities, $result);
    }

    public function testGetDefaultLocalization()
    {
        $localization1 = $this->getEntity(Localization::class, ['id' => 1]);
        $localization2 = $this->getEntity(Localization::class, ['id' => 2]);

        $this->repository->expects($this->never())->method('find');
        $this->repository->expects($this->once())->method('findBy')
            ->willReturn([1 => $localization1, 2 => $localization2]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn(1);

        $this->assertSame($localization1, $this->provider->getDefaultLocalization());
    }

    public function testGetDefaultLocalizationAndNoDefaultLocalization()
    {
        $localization1 = $this->getEntity(Localization::class, ['id' => 1]);
        $localization2 = $this->getEntity(Localization::class, ['id' => 2]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn(false);

        $this->repository->expects($this->never())->method('find');
        $this->repository->expects($this->once())->method('findBy')
            ->willReturn([1 => $localization1, 2 => $localization2]);

        $this->assertSame($localization1, $this->provider->getDefaultLocalization());
    }

    public function testGetDefaultLocalizationAndNoDefaultLocalizationAndNoLocalizations()
    {
        $this->repository->expects($this->never())->method('find');
        $this->repository->expects($this->once())->method('findBy')->willReturn([]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn(false);

        $this->assertNull($this->provider->getDefaultLocalization());
    }

    public function testGetDefaultLocalizationAndUnknownConfigDefaultLocalization()
    {
        $localization1 = $this->getEntity(Localization::class, ['id' => 1]);
        $localization2 = $this->getEntity(Localization::class, ['id' => 2]);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn(13);

        $this->repository->expects($this->never())->method('find');
        $this->repository->expects($this->once())->method('findBy')
            ->willReturn([1 => $localization1, 2 => $localization2]);

        $this->assertSame($localization1, $this->provider->getDefaultLocalization());
    }

    public function testGetCurrentLocalizationAndNoExtensions()
    {
        $this->assertNull($this->provider->getCurrentLocalization());
    }

    public function testGetCurrentLocalization()
    {
        $localization = new Localization();

        $extension1 = $this->getMock(CurrentLocalizationExtensionInterface::class);
        $extension2 = $this->getMock(CurrentLocalizationExtensionInterface::class);
        $extension3 = $this->getMock(CurrentLocalizationExtensionInterface::class);

        $extension1->expects($this->once())->method('getCurrentLocalization')->willReturn(null);
        $extension2->expects($this->once())->method('getCurrentLocalization')->willReturn($localization);
        $extension3->expects($this->never())->method('getCurrentLocalization');

        $this->provider->addExtension('e1', $extension1);
        $this->provider->addExtension('e2', $extension2);
        $this->provider->addExtension('e3', $extension3);

        $this->assertSame($localization, $this->provider->getCurrentLocalization());
    }

    public function testWarmUpCache()
    {
        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn($this->entities);

        $this->provider->warmUpCache();
    }

    protected function assertRepositoryCalls()
    {
        $this->repository->expects($this->never())->method('find');
        $this->repository->expects($this->once())->method('findBy')->willReturn($this->entities);
    }
}
