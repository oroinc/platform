<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSettingsInterface;
use Oro\Bundle\ApiBundle\Config\EntityConfigMerger;

class EntityConfigMergerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigExtensionRegistry */
    private $configExtensionRegistry;

    /** @var EntityConfigMerger */
    private $entityConfigMerger;

    protected function setUp()
    {
        $this->configExtensionRegistry = $this->createMock(ConfigExtensionRegistry::class);

        $this->entityConfigMerger = new EntityConfigMerger(
            $this->configExtensionRegistry
        );
    }

    public function testMerge()
    {
        $config = [
            'documentation_resource' => 'documentation resource'
        ];
        $parentConfig = [
            'documentation_resource' => 'parent documentation resource',
            'exclusion_policy'       => 'all'
        ];

        $configurationSettings = $this->createMock(ConfigurationSettingsInterface::class);
        $configurationSettings->expects(self::any())
            ->method('getExtraSections')
            ->willReturn([]);
        $configurationSettings->expects(self::any())
            ->method('getConfigureCallbacks')
            ->willReturn([]);
        $configurationSettings->expects(self::any())
            ->method('getPreProcessCallbacks')
            ->willReturn([]);
        $configurationSettings->expects(self::any())
            ->method('getPostProcessCallbacks')
            ->willReturn([]);
        $this->configExtensionRegistry->expects(self::once())
            ->method('getConfigurationSettings')
            ->willReturn($configurationSettings);
        $this->configExtensionRegistry->expects(self::once())
            ->method('getMaxNestingLevel')
            ->willReturn(0);

        self::assertEquals(
            [
                'documentation_resource' => ['parent documentation resource', 'documentation resource'],
                'exclusion_policy'       => 'all'
            ],
            $this->entityConfigMerger->merge($config, $parentConfig)
        );
    }
}
