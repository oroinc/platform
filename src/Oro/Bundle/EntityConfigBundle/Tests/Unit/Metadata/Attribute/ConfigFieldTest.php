<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Attribute;

use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Exception\AttributeException;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use PHPUnit\Framework\TestCase;

class ConfigFieldTest extends TestCase
{
    public function testConstructorDefaultValues(): void
    {
        $expectedMode = ConfigModel::MODE_DEFAULT;
        $expectedDefaultValues = [];

        $configField = new ConfigField();
        $this->assertEquals($expectedMode, $configField->mode);
        $this->assertEquals($expectedDefaultValues, $configField->defaultValues);
    }

    public function testConstructor(): void
    {
        $expectedMode = ConfigModel::MODE_READONLY;
        $expectedDefaultValues = ['test' => 'test_val'];

        $configField = new ConfigField(
            mode: ConfigModel::MODE_READONLY,
            defaultValues: ['test' => 'test_val'],
        );

        $this->assertEquals($expectedMode, $configField->mode);
        $this->assertEquals($expectedDefaultValues, $configField->defaultValues);
    }

    public function testConstructorWithValueValue(): void
    {
        $expectedMode = ConfigModel::MODE_HIDDEN;
        $expectedDefaultValues = [];

        $configField = new ConfigField(
            mode: ConfigModel::MODE_READONLY,
            value: ConfigModel::MODE_HIDDEN,
        );

        $this->assertEquals($expectedMode, $configField->mode);
        $this->assertEquals($expectedDefaultValues, $configField->defaultValues);
    }

    public function testAttributeExceptionInvalidMode(): void
    {
        $this->expectException(AttributeException::class);
        $this->expectExceptionMessage('Attribute "ConfigField" has an invalid value parameter "mode" : "some mode"');

        new ConfigField(mode: 'some mode');
    }

    public function testAttributeExceptionNonSupportedArgument(): void
    {
        $this->expectException(AttributeException::class);
        $this->expectExceptionMessage(
            'Attribute "ConfigField" does not support argument : "unSupportedArgument"'
        );

        new ConfigField(unSupportedArgument: 'tst_argument_value');
    }

    /**
     * @dataProvider arraysDataProvider
     */
    public function testAttributeExceptionArrayAsArgument($data): void
    {
        $this->expectException(AttributeException::class);
        $this->expectExceptionMessage(
            'Attribute "ConfigField" does not support array as an argument. Use named arguments instead.'
        );

        new ConfigField($data);
    }

    public function arraysDataProvider(): array
    {
        return [
            [
                []
            ],
            [
                [
                    'mode' => ConfigModel::MODE_READONLY,
                    'routeName' => 'test_route_name',
                    'routeView' => 'test_route_view',
                ]
            ],
            [
                [
                    'defaultValues' => [
                        'test' => 'test_val'
                    ]
                ]
            ]
        ];
    }
}
