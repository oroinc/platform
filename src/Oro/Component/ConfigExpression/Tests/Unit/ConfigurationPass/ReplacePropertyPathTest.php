<?php

namespace Oro\Component\ConfigExpression\Tests\Unit\ConfigurationPass;

use Oro\Component\ConfigExpression\ConfigurationPass\ReplacePropertyPath;
use Oro\Component\ConfigExpression\PropertyAccess\PropertyPath;

class ReplacePropertyPathTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider passDataProvider
     */
    public function testPassConfiguration(array $sourceData, array $expectedData)
    {
        $parameterPass = new ReplacePropertyPath();
        $actualData    = $parameterPass->passConfiguration($sourceData);

        $this->assertEquals($expectedData, $actualData);
    }

    /**
     * @return array
     */
    public function passDataProvider()
    {
        return [
            [
                'sourceData'   => [],
                'expectedData' => []
            ],
            [
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
            ]
        ];
    }
}
