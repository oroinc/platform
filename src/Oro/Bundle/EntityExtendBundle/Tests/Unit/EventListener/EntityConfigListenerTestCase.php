<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Audit\AuditManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigCache;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Metadata\Factory\MetadataFactory;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock\ConfigurationHandlerMock;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityConfigListenerTestCase extends TestCase
{
    protected ConfigManager $configManager;
    protected ConfigProvider&MockObject $configProvider;
    protected ConfigCache&MockObject $configCache;
    protected EventDispatcherInterface&MockObject $eventDispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->configCache = $this->createMock(ConfigCache::class);

        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->configProvider->expects($this->any())
            ->method('getScope')
            ->willReturn('extend');

        $configProviderBag = $this->createMock(ConfigProviderBag::class);
        $configProviderBag->expects($this->any())
            ->method('getProvider')
            ->willReturnCallback(function ($scope) {
                return 'extend' === $scope
                    ? $this->configProvider
                    : null;
            });
        $serviceProvider = new ServiceLocator([
            'annotation_metadata_factory' => function () {
                return $this->createMock(MetadataFactory::class);
            },
            'configuration_handler' => function () {
                return ConfigurationHandlerMock::getInstance();
            },
            'event_dispatcher' => function () {
                return $this->eventDispatcher;
            },
            'audit_manager' => function () {
                return $this->createMock(AuditManager::class);
            },
            'config_model_manager' => function () {
                return $this->createMock(ConfigModelManager::class);
            }
        ]);

        $this->configManager = new ConfigManager(
            $this->configCache,
            $serviceProvider
        );
        $this->configManager->setProviderBag($configProviderBag);
    }
}
