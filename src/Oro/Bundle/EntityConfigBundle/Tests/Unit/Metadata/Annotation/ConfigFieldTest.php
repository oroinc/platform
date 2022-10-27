<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Annotation;

use Oro\Bundle\EntityConfigBundle\Exception\AnnotationException;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

class ConfigFieldTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider constructorDataProvider
     */
    public function testConstructor(
        array $data,
        string $expectedMode,
        array $expectedDefaultValues
    ) {
        $config = new ConfigField($data);
        $this->assertEquals($expectedMode, $config->mode);
        $this->assertEquals($expectedDefaultValues, $config->defaultValues);
    }

    public function testIncorrectMode()
    {
        $this->expectException(AnnotationException::class);
        new ConfigField(['mode' => 'some mode']);
    }

    public function constructorDataProvider(): array
    {
        return [
            [
                [],
                'default',
                [],
            ],
            [
                ['mode' => 'readonly'],
                'readonly',
                [],
            ],
            [
                ['value' => 'readonly'],
                'readonly',
                [],
            ],
            [
                [
                    'mode'          => 'readonly',
                    'defaultValues' => [
                        'test' => 'test_val'
                    ]
                ],
                'readonly',
                [
                    'test' => 'test_val'
                ],
            ],
        ];
    }
}
