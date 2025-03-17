<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\Entity\Config;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;
use Oro\Bundle\ConfigBundle\Entity\Repository\ConfigValueRepository;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\LocaleBundle\EventListener\LocalizationChangeListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LocalizationChangeListenerTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private ConfigValueRepository&MockObject $repository;
    private LocalizationChangeListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->configManager->expects($this->any())
            ->method('getScopeEntityName')
            ->willReturn('user');

        $this->repository = $this->createMock(ConfigValueRepository::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->listener = new LocalizationChangeListener($this->configManager, $doctrine);
    }

    public function testOnConfigUpdate(): void
    {
        $value1 = $this->getConfigValue(1001, 42);
        $value2 = $this->getConfigValue(1002, 43);

        $this->repository->expects($this->once())
            ->method('getConfigValues')
            ->with(
                $this->configManager->getScopeEntityName(),
                'oro_locale',
                'default_localization'
            )
            ->willReturn([$value1, $value2]);

        $this->configManager->expects($this->once())
            ->method('reset')
            ->with('oro_locale.default_localization', $value1->getConfig()->getRecordId());
        $this->configManager->expects($this->once())
            ->method('flush')
            ->with($value1->getConfig()->getRecordId());

        $event = new ConfigUpdateEvent(
            [
                'oro_locale.enabled_localizations' => [
                    'new' => [$value2->getValue(), 46],
                    'old' => [$value1->getValue(), $value2->getValue()]
                ]
            ],
            'global',
            0
        );

        $this->listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateNotChanged(): void
    {
        $this->repository->expects($this->never())
            ->method('getConfigValues');

        $this->configManager->expects($this->never())
            ->method('reset');
        $this->configManager->expects($this->never())
            ->method('flush');

        $event = new ConfigUpdateEvent(['oro_locale.default_localization' => ['new' => 43, 'old' => 42]], 'global', 0);

        $this->listener->onConfigUpdate($event);
    }

    public function testOnConfigUpdateNotGlobalScope(): void
    {
        $this->repository->expects($this->never())
            ->method('getConfigValues');

        $this->configManager->expects($this->never())
            ->method('reset');
        $this->configManager->expects($this->never())
            ->method('flush');

        $event = new ConfigUpdateEvent(
            [
                'oro_locale.enabled_localizations' => [
                    'new' => [43, 46],
                    'old' => [42, 43]
                ]
            ],
            'user',
            1
        );

        $this->listener->onConfigUpdate($event);
    }

    private function getConfigValue(int $recordId, int $value): ConfigValue
    {
        $config = new Config();
        $config->setRecordId($recordId);

        $configValue = new ConfigValue();

        return $configValue->setConfig($config)
            ->setValue($value);
    }
}
