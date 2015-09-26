<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class EntityConfigListenerTestCase extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configCache;

    protected function setUp()
    {
        $eventDispatcher    = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataFactory    = $this->getMockBuilder('Metadata\MetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $entityChecker      = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\EntityChecker')
            ->disableOriginalConstructor()
            ->getMock();
        $modelManager       = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager')
            ->disableOriginalConstructor()
            ->getMock();
        $auditEntityBuilder = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\AuditEntityBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configCache = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigCache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = new ConfigManager(
            $eventDispatcher,
            $metadataFactory,
            $entityChecker,
            $modelManager,
            $auditEntityBuilder,
            $this->configCache
        );

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider->expects($this->any())
            ->method('getScope')
            ->willReturn('extend');

        $this->configManager->addProvider($this->configProvider);
    }
}
