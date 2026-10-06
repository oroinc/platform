<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\Definition\ConfigurationSettingsInterface;
use Oro\Bundle\ApiBundle\Config\EntityConfigMerger;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityConfigMergerTest extends TestCase
{
    private ConfigExtensionRegistry&MockObject $configExtensionRegistry;
    private EntityConfigMerger $entityConfigMerger;

    #[\Override]
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
                        'field2' => ['data_type' => 'string', 'form_type' => 'Form1'],
                        'field4' => ['data_type' => 'string'],
                        'field5' => ['data_type' => 'string', 'property_path' => 'property5'],
                        'field6' => ['data_type' => 'string', 'property_path' => 'property6']
                    ]
                ],
                'config'       => [
                    'fields' => [
                        'field2' => ['data_type' => 'integer', 'form_options' => ['k' => 'v']],
                        'field3' => ['data_type' => 'string'],
                        'renamedField4' => ['property_path' => 'field4'],
                        'renamedField5' => ['property_path' => 'property5'],
                        'field6' => ['property_path' => 'property6', 'form_type' => 'Form2']
                    ]
                ],
                'mergedConfig' => [
                    'fields' => [
                        'field1' => ['data_type' => 'string'],
                        'field2' => ['data_type' => 'integer', 'form_type' => 'Form1', 'form_options' => ['k' => 'v']],
                        'field3' => ['data_type' => 'string'],
                        'renamedField4' => ['property_path' => 'field4', 'data_type' => 'string'],
                        'renamedField5' => ['property_path' => 'property5', 'data_type' => 'string'],
                        'field6' => ['data_type' => 'string', 'property_path' => 'property6', 'form_type' => 'Form2']
                    ]
                ]
            ],
            'fields when the source field is overridden' => [
                'parentConfig' => [
                    'fields' => [
                        'sku' => ['data_type' => 'string', 'property_path' => 'text.sku'],
                        'product' => [
                            'data_type' => 'integer',
                            'property_path' => 'integer.system_entity_id',
                            'form_type' => 'Form1'
                        ]
                    ]
                ],
                'config'       => [
                    'fields' => [
                        'productId' => ['data_type' => 'integer', 'property_path' => 'integer.system_entity_id'],
                        'product' => ['data_type' => 'string', 'property_path' => 'text.sku']
                    ]
                ],
                'mergedConfig' => [
                    'fields' => [
                        'sku' => ['data_type' => 'string', 'property_path' => 'text.sku'],
                        'product' => ['data_type' => 'string', 'property_path' => 'text.sku', 'form_type' => 'Form1'],
                        'productId' => ['data_type' => 'integer', 'property_path' => 'integer.system_entity_id']
                    ]
                ]
            ],
            'fields when the parent config has a field with the same name' => [
                'parentConfig' => [
                    'fields' => [
                        'field1' => ['data_type' => 'string', 'property_path' => 'property1'],
                        'newField' => ['data_type' => 'integer']
                    ]
                ],
                'config'       => [
                    'fields' => [
                        'newField' => ['property_path' => 'property1']
                    ]
                ],
                'mergedConfig' => [
                    'fields' => [
                        'field1' => ['data_type' => 'string', 'property_path' => 'property1'],
                        'newField' => ['data_type' => 'integer', 'property_path' => 'property1']
                    ]
                ]
            ]
        ];
    }
}
