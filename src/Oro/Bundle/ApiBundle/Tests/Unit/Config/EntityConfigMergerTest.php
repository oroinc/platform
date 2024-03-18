<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSettingsInterface;
use Oro\Bundle\ApiBundle\Config\EntityConfigMerger;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;

class EntityConfigMergerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigExtensionRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $configExtensionRegistry;

    /** @var EntityConfigMerger */
    private $entityConfigMerger;

    protected function setUp(): void
    {
        $this->configExtensionRegistry = $this->createMock(ConfigExtensionRegistry::class);

        $this->entityConfigMerger = new EntityConfigMerger(
            $this->configExtensionRegistry
        );
    }

    /**
     * @dataProvider mergeDataProvider
     */
    public function testMerge(array $parentConfig, array $config, array $mergedConfig): void
    {
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

        self::assertEquals($mergedConfig, $this->entityConfigMerger->merge($config, $parentConfig));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function mergeDataProvider(): array
    {
        return [
            'no fields'                      => [
                'parentConfig' => [
                    'documentation_resource' => 'parent documentation resource',
                    'exclusion_policy'       => 'all'
                ],
                'config'       => [
                    'documentation_resource' => 'documentation resource'
                ],
                'mergedConfig' => [
                    'documentation_resource' => ['parent documentation resource', 'documentation resource'],
                    'exclusion_policy'       => 'all'
                ]
            ],
            'fields'                         => [
                'parentConfig' => [
                    'fields' => [
                        'field1' => ['data_type' => 'string'],
                        'field2' => ['data_type' => 'string', 'form_type' => 'Form1']
                    ]
                ],
                'config'       => [
                    'fields' => [
                        'field2' => ['data_type' => 'integer', 'form_options' => ['k' => 'v']],
                        'field3' => ['data_type' => 'string']
                    ]
                ],
                'mergedConfig' => [
                    'fields' => [
                        'field1' => ['data_type' => 'string'],
                        'field2' => ['data_type' => 'integer', 'form_type' => 'Form1', 'form_options' => ['k' => 'v']],
                        'field3' => ['data_type' => 'string']
                    ]
                ]
            ]
        ];
    }
}
