<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormOptionsByFieldTypeProvider;
use Oro\Bundle\EntityExtendBundle\Provider\ExtendFieldFormTypeProvider;

class ExtendFieldFormOptionsByFieldTypeProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityConfigManager;

    /** @var ExtendFieldFormTypeProvider */
    private $extendFieldFormTypeProvider;

    /** @var ExtendFieldFormOptionsByFieldTypeProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->entityConfigManager = $this->createMock(EntityConfigManager::class);
        $this->extendFieldFormTypeProvider = new ExtendFieldFormTypeProvider();

        $this->provider = new ExtendFieldFormOptionsByFieldTypeProvider(
            $this->entityConfigManager,
            $this->extendFieldFormTypeProvider
        );
    }

    /**
     * @dataProvider getOptionsWhenScalarFieldTypeDataProvider
     */
    public function testGetOptionsWhenScalarFieldType(string $fieldType, array $expectedOptions): void
    {
        $className = \stdClass::class;
        $fieldName = 'sampleField';
        $formFieldConfig = new Config(new FieldConfigId('form', $className, $fieldName, $fieldType), []);

        $this->entityConfigManager->expects(self::once())
            ->method('getFieldConfig')
            ->with('form', $className, $fieldName)
            ->willReturn($formFieldConfig);

        $formOptionsForFieldType = ['sample_key' => 'sample_value'];
        $this->extendFieldFormTypeProvider->addExtendTypeMapping($fieldType, 'CustomType', $formOptionsForFieldType);

        self::assertEquals(
            $expectedOptions,
            $this->provider->getOptions($className, $fieldName)
        );
    }

    public function getOptionsWhenScalarFieldTypeDataProvider(): array
    {
        return [
            'boolean' => [
                'fieldType' => 'boolean',
                'expectedOptions' => [
                    'configs' => ['allowClear' => false],
                    'choices' => [
                        'No' => false,
                        'Yes' => true,
                    ],
                    'sample_key' => 'sample_value',
                ],
            ],
            'float' => [
                'fieldType' => 'float',
                'expectedOptions' => [
                    'grouping' => true,
                    'sample_key' => 'sample_value',
                ],
            ],
            'decimal' => [
                'fieldType' => 'decimal',
                'expectedOptions' => [
                    'grouping' => true,
                    'sample_key' => 'sample_value',
                ],
            ],
        ];
    }

    /**
     * @dataProvider getOptionsWhenEnumFieldTypeDataProvider
     */
    public function testGetOptionsWhenEnumFieldType(string $fieldType, array $expectedOptions): void
    {
        $className = \stdClass::class;
        $fieldName = 'sampleField';
        $formFieldConfig = new Config(new FieldConfigId('form', $className, $fieldName, $fieldType), []);
        $enumFieldConfig = new Config(
            new FieldConfigId('enum', $className, $fieldName, $fieldType),
            ['enum_code' => 'sample_enum']
        );

        $this->entityConfigManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                ['form', $className, $fieldName, $formFieldConfig],
                ['enum', $className, $fieldName, $enumFieldConfig],
            ]);

        $formOptionsForFieldType = ['sample_key' => 'sample_value'];
        $this->extendFieldFormTypeProvider->addExtendTypeMapping($fieldType, 'CustomType', $formOptionsForFieldType);

        self::assertEquals(
            $expectedOptions,
            $this->provider->getOptions($className, $fieldName)
        );
    }

    public function getOptionsWhenEnumFieldTypeDataProvider(): array
    {
        return [
            'enum' => [
                'fieldType' => 'enum',
                'expectedOptions' => [
                    'enum_code' => 'sample_enum',
                    'sample_key' => 'sample_value',
                ],
            ],
            'multiEnum' => [
                'fieldType' => 'multiEnum',
                'expectedOptions' => [
                    'enum_code' => 'sample_enum',
                    'expanded' => true,
                    'sample_key' => 'sample_value',

                ],
            ],
        ];
    }

    /**
     * @dataProvider getOptionsWhenRelationFieldTypeDataProvider
     */
    public function testGetOptionsWhenRelationFieldType(
        string $fieldType,
        $extendScopeOptions,
        array $expectedOptions
    ): void {
        $className = \stdClass::class;
        $fieldName = 'sampleField';
        $formFieldConfig = new Config(new FieldConfigId('form', $className, $fieldName, $fieldType), []);
        $extendFieldConfig = new Config(
            new FieldConfigId('extend', $className, $fieldName, $fieldType),
            $extendScopeOptions
        );

        $this->entityConfigManager->expects(self::exactly(2))
            ->method('getFieldConfig')
            ->willReturnMap([
                ['form', $className, $fieldName, $formFieldConfig],
                ['extend', $className, $fieldName, $extendFieldConfig],
            ]);

        $formOptionsForFieldType = ['sample_key' => 'sample_value'];
        $this->extendFieldFormTypeProvider->addExtendTypeMapping($fieldType, 'CustomType', $formOptionsForFieldType);

        self::assertEquals(
            $expectedOptions,
            $this->provider->getOptions($className, $fieldName)
        );
    }

    public function getOptionsWhenRelationFieldTypeDataProvider(): array
    {
        return [
            'many-to-one' => [
                'fieldType' => RelationType::MANY_TO_ONE,
                'extendScopeOptions' => [
                    'target_entity' => 'Acme\Bundle\SampleBundle\Entity\Sample',
                    'target_field' => 'sampleField',
                ],
                'expectedOptions' => [
                    'entity_class' => 'Acme\Bundle\SampleBundle\Entity\Sample',
                    'configs' => [
                        'placeholder' => 'oro.form.choose_value',
                        'component' => 'relation',
                        'target_entity' => 'Acme_Bundle_SampleBundle_Entity_Sample',
                        'target_field' => 'sampleField',
                        'properties' => ['sampleField'],
                    ],
                    'sample_key' => 'sample_value',
                ],
            ],
            'one-to-many' => [
                'fieldType' => RelationType::ONE_TO_MANY,
                'extendScopeOptions' => [
                    'target_entity' => 'Acme\Bundle\SampleBundle\Entity\Sample',
                    'target_field' => 'sampleField',
                ],
                'expectedOptions' => [
                    'block' => 'Sample',
                    'block_config' => ['Sample' => ['title' => null, 'subblocks' => [['useSpan' => false]]]],
                    'class' => 'Acme\Bundle\SampleBundle\Entity\Sample',
                    'selector_window_title' => 'Select Sample',
                    'initial_elements' => null,
                    'default_element' => 'default_sampleField',
                    'sample_key' => 'sample_value',
                ],
            ],
            'many-to-many without default' => [
                'fieldType' => RelationType::MANY_TO_MANY,
                'extendScopeOptions' => [
                    'target_entity' => 'Acme\Bundle\SampleBundle\Entity\Sample',
                    'target_field' => 'sampleField',
                    'without_default' => true,
                ],
                'expectedOptions' => [
                    'block' => 'Sample',
                    'block_config' => ['Sample' => ['title' => null, 'subblocks' => [['useSpan' => false]]]],
                    'class' => 'Acme\Bundle\SampleBundle\Entity\Sample',
                    'selector_window_title' => 'Select Sample',
                    'initial_elements' => null,
                    'sample_key' => 'sample_value',
                ],
            ],
        ];
    }
}
