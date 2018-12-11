<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\LocaleBundle\EventListener\LocalizationChangeListener;

class LocalizationChangeListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ConfigValueRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var LocalizationChangeListener */
    private $listener;

    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->repository = $this->createMock(ConfigValueRepository::class);

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())->method('getRepository')->willReturn($this->repository);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $managerRegistry */
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())->method('getManagerForClass')->willReturn($entityManager);

        $this->listener = new LocalizationChangeListener($managerRegistry);
    }

    /**
     * @dataProvider onConfigUpdateDataProvider
     *
     * @param array $codes
     * @param $expectedUpdateCount
     * @param bool $isChanged
     * @param bool $isGlobal
     */
    public function testOnConfigUpdate(
        array $locales,
        $repositoryCallsCount,
        $expectedUpdateCount,
        $isChanged,
        $isGlobal
    ) {
        $event = $this->getEvent($isChanged, $isGlobal);

        $configValues = [];
        $config = new Config();
        $config->setRecordId(1);

        foreach ($locales as $locale) {
            $configValue = new ConfigValue();
            $configValue->setValue($locale)->setConfig($config);
            $configValues[] = $configValue;
        }

        $this->repository->expects($this->exactly($repositoryCallsCount))
            ->method('getConfigValues')
            ->willReturn($configValues);

        $this->configManager->expects($this->exactly($expectedUpdateCount))->method('reset');
        $this->configManager->expects($this->exactly($expectedUpdateCount))->method('flush');

        $this->listener->onConfigUpdate($event);
    }

    /**
     * @return array
     */
    public function onConfigUpdateDataProvider()
    {
        return [
            'not changed' => [
                'locales' => [],
                'repositoryCallsCount' => 0,
                'expectedUpdateCount' => 0,
                'isChanged' => false,
                'isGlobal' => true
            ],
            'not global' => [
                'locales' => [],
                'repositoryCallsCount' => 0,
                'expectedUpdateCount' => 0,
                'isChanged' => false,
                'isGlobal' => true
            ]
        ];
    }

    /**
     * @param bool $isChanged
     * @param bool $isGlobal
     *
     * @return ConfigUpdateEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getEvent($isChanged = false, $isGlobal = false)
    {
        $event = $this->createMock(ConfigUpdateEvent::class);

        $event->expects($this->once())
            ->method('isChanged')
            ->with('oro_locale.default_localization')
            ->willReturn($isChanged);

        $event->expects($this->exactly((int) $isChanged))
            ->method('getScope')
            ->willReturn($isGlobal ? 'global' : '');

        return $event;
    }
}
