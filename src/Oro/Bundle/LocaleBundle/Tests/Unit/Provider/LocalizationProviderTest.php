<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Provider;

use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProvider;

use Oro\Component\Testing\Unit\EntityTrait;

class LocalizationProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ObjectRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $repository;

    /** @var LocalizationProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    public function setUp()
    {
        $this->repository = $this->getMockBuilder(ObjectRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new LocalizationProvider($this->repository, $this->configManager);
    }

    public function tearDown()
    {
        unset($this->registry, $this->provider);
    }

    public function testGetLocalization()
    {
        /** @var Localization $entity */
        $entity = $this->getEntity(Localization::class, ['id' => 1]);

        $this->repository->expects($this->once())
            ->method('find')
            ->with($entity->getId())
            ->willReturn($entity);

        $result = $this->provider->getLocalization($entity->getId());

        $this->assertEquals($entity, $result);
    }

    public function testGetLocalizations()
    {
        /** @var Localization[] $entities */
        $entities = [
            $this->getEntity(Localization::class, ['id' => 1]),
            $this->getEntity(Localization::class, ['id' => 2]),
            $this->getEntity(Localization::class, ['id' => 3]),
        ];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->willReturn($entities);

        $result = $this->provider->getLocalizations();

        $this->assertEquals($entities, $result);
    }

    public function testGetLocalizationsByIds()
    {
        /** @var Localization[] $entities */
        $entities = [
            $this->getEntity(Localization::class, ['id' => 1]),
            $this->getEntity(Localization::class, ['id' => 3]),
        ];

        $ids = [1, 3];

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with(['id' => $ids])
            ->willReturn($entities);

        $result = $this->provider->getLocalizations((array)$ids);

        $this->assertEquals($entities, $result);
    }

    public function testGetDefaultLocalization()
    {
        $localization = new Localization();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn(1);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($localization);

        $this->assertSame($localization, $this->provider->getDefaultLocalization());
    }

    public function testGetDefaultLocalizationAndNoDefaultLocalization()
    {
        $localization1 = new Localization();
        $localization2 = new Localization();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn(1);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with([], ['name' => 'ASC'])
            ->willReturn([$localization1, $localization2]);

        $this->assertSame($localization1, $this->provider->getDefaultLocalization());
    }

    public function testGetDefaultLocalizationAndNoDefaultLocalizationAndNoLocalizations()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with(Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION))
            ->willReturn(1);

        $this->repository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('findBy')
            ->with([], ['name' => 'ASC'])
            ->willReturn([]);

        $this->assertNull($this->provider->getDefaultLocalization());
    }
}
