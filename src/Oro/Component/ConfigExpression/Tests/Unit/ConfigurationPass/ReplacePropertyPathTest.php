<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\ConfigurationPass;

use Oro\Component\ConfigExpression\ConfigurationPass\ReplacePropertyPath;
use Symfony\Component\PropertyAccess\PropertyPath;

class ReplacePropertyPathTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider passDataProvider
     *
     * @param array $sourceData
     * @param array $expectedData
     * @param string $prefix
     */
    public function testPassConfiguration(array $sourceData, array $expectedData, $prefix = null)
    {
        $parameterPass = new ReplacePropertyPath($prefix);
        $actualData    = $parameterPass->passConfiguration($sourceData);

        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public function passDataProvider()
    {
        return [
            'empty data' => [
                'sourceData'   => [],
                'expectedData' => []
            ],
            'data with paths' => [
                'sourceData'   => [
                    'a' => '$path.component',
                    'b' => ['c' => '$another.path.component'],
                    'c' => '\$path.component'
                ],
                'expectedData' => [
                    'a' => new PropertyPath('path.component'),
                    'b' => ['c' => new PropertyPath('another.path.component')],
                    'c' => '$path.component'
                ]
            ],
            'data with prefix' => [
                'sourceData' => [
                    'a' => '$path.component',
                    'b' => ['c' => '$another.path.component'],
                    'c' => '\$path.component'
                ],
                'expectedData' => [
                    'a' => new PropertyPath('prefix.path.component'),
                    'b' => ['c' => new PropertyPath('prefix.another.path.component')],
                    'c' => '$path.component'
                ],
                'prefix' => 'prefix'
            ],
            'data with root ignore prefix' => [
                'sourceData' => [
                    'a' => '$.path.component',
                    'b' => [
                        'c' => '$.another.path.component'
                    ]
                ],
                'expectedData' => [
                    'a' => new PropertyPath('path.component'),
                    'b' => ['c' => new PropertyPath('another.path.component')]
                ],
                'prefix' => 'prefix'
            ],
        ];
    }

    public function testLocalCache()
    {
        $parameterPass = new ReplacePropertyPath();
        $actualData    = $parameterPass->passConfiguration(['a' => '$path']);

        $this->assertEquals(
            ['a' => new PropertyPath('path')],
            $actualData
        );

        $propertyPath = $actualData['a'];

        $actualData = $parameterPass->passConfiguration(['b' => '$path']);
        $this->assertEquals(
            ['b' => new PropertyPath('path')],
            $actualData
        );

        $this->assertSame($propertyPath, $actualData['b']);
    }
}
