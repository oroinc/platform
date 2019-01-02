<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\EventListener;

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
        $this->configManager->expects($this->any())
            ->method('getScopeEntityName')
            ->willReturn('user');

        $this->repository = $this->createMock(ConfigValueRepository::class);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $managerRegistry */
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->listener = new LocalizationChangeListener($this->configManager, $managerRegistry);
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
            'global'
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

        $event = new ConfigUpdateEvent(['oro_locale.default_localization' => ['new' => 43, 'old' => 42]], 'global');

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
            'user'
        );

        $this->listener->onConfigUpdate($event);
    }

    /**
     * @param int $recordId
     * @param int $value
     * @return ConfigValue
     */
    private function getConfigValue(int $recordId, int $value): ConfigValue
    {
        $config = new Config();
        $config->setRecordId($recordId);

        $configValue = new ConfigValue();

        return $configValue->setConfig($config)
            ->setValue($value);
    }
}
