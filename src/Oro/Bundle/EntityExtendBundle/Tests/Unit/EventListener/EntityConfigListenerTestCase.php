<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Metadata\MetadataFactory;
use Oro\Bundle\EntityConfigBundle\Audit\AuditManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigCache;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderBagMock;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EntityConfigListenerTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    /** @var ConfigCache|\PHPUnit\Framework\MockObject\MockObject */
    protected $configCache;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    protected function setUp()
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        /** @var MetadataFactory|\PHPUnit\Framework\MockObject\MockObject $metadataFactory */
        $metadataFactory = $this->createMock(MetadataFactory::class);

        /** @var ConfigModelManager|\PHPUnit\Framework\MockObject\MockObject $modelManager */
        $modelManager = $this->createMock(ConfigModelManager::class);

        /** @var AuditManager|\PHPUnit\Framework\MockObject\MockObject $auditManager */
        $auditManager = $this->createMock(AuditManager::class);

        $this->configCache = $this->createMock(ConfigCache::class);

        $this->configManager = new ConfigManager(
            $this->eventDispatcher,
            $metadataFactory,
            $modelManager,
            $auditManager,
            $this->configCache
        );

        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->configProvider->expects($this->any())
            ->method('getScope')
            ->willReturn('extend');

        $configProviderBag = new ConfigProviderBagMock();
        $configProviderBag->addProvider($this->configProvider);
        $this->configManager->setProviderBag($configProviderBag);
    }
}
