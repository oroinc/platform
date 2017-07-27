<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Config\Shared\MergeConfig;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSettingsInterface;
use Oro\Bundle\ApiBundle\Processor\Config\Shared\MergeConfig\MergeEntityConfigHelper;

class MergeEntityConfigHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configExtensionRegistry;

    /** @var MergeEntityConfigHelper */
    protected $mergeEntityConfigHelper;

    protected function setUp()
    {
        $this->configExtensionRegistry = $this->getMockBuilder(ConfigExtensionRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mergeEntityConfigHelper = new MergeEntityConfigHelper(
            $this->configExtensionRegistry
        );
    }

    public function testMergeConfigs()
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
                'documentation_resource' => 'documentation resource',
                'exclusion_policy'       => 'all'
            ],
            $this->mergeEntityConfigHelper->mergeConfigs($config, $parentConfig)
        );
    }
}
