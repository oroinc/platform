<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Metadata\Annotation;

use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;

class ConfigFieldTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider constructorDataProvider
     */
    public function testConstructor(
        $data,
        $expectedMode,
        $expectedDefaultValues
    ) {
        $config = new ConfigField($data);
        $this->assertEquals($expectedMode, $config->mode);
        $this->assertEquals($expectedDefaultValues, $config->defaultValues);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\AnnotationException
     */
    public function testIncorrectMode()
    {
        new ConfigField(['mode' => 'some mode']);
    }

    /**
     * @expectedException \Oro\Bundle\EntityConfigBundle\Exception\AnnotationException
     */
    public function testIncorrectDefaultValues()
    {
        new ConfigField(['defaultValues' => 'some string']);
    }

    public function constructorDataProvider()
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
