<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderBagMock;

class EntityConfigListenerTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $configCache;

    protected function setUp()
    {
        $eventDispatcher    = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory    = $this->getMockBuilder('Metadata\MetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $modelManager       = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager')
            ->disableOriginalConstructor()
            ->getMock();
        $auditManager       = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Audit\AuditManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configCache = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigCache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = new ConfigManager(
            $eventDispatcher,
            $metadataFactory,
            $modelManager,
            $auditManager,
            $this->configCache
        );

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider->expects($this->any())
            ->method('getScope')
            ->willReturn('extend');

        $configProviderBag = new ConfigProviderBagMock();
        $configProviderBag->addProvider($this->configProvider);
        $this->configManager->setProviderBag($configProviderBag);
    }
}
