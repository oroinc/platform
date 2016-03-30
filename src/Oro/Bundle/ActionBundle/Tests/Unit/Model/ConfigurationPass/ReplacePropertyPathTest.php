<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\ConfigurationPass;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\ActionBundle\Model\ConfigurationPass\ReplacePropertyPath;

class ReplacePropertyPathTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $sourceData
     * @param array $expectedData
     * @param string $prefix
     *
     * @dataProvider passDataProvider
     */
    public function testPassConfiguration(array $sourceData, array $expectedData, $prefix = null)
    {
        $parameterPass = new ReplacePropertyPath($prefix);
        $actualData = $parameterPass->passConfiguration($sourceData);

        $this->assertEquals($expectedData, $this->replacePropertyPathsWithElements($actualData));
    }

    /**
     * @param array $data
     * @return array
     */
    protected function replacePropertyPathsWithElements($data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->replacePropertyPathsWithElements($value);
            } elseif ($value instanceof PropertyPath) {
                $data[$key] = $value->getElements();
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function passDataProvider()
    {
        return [
            'empty data' => [
                'sourceData' => [],
                'expectedData' => []
            ],
            'data with paths' => [
                'sourceData' => [
                    'a' => '$path.component',
                    'b' => [
                        'c' => '$another.path.component'
                    ]
                ],
                'expectedData' => [
                    'a' => ['path', 'component'],
                    'b' => [
                        'c' => ['another', 'path', 'component'],
                    ]
                ]
            ],
            'data with prefix' => [
                'sourceData' => [
                    'a' => '$path.component',
                    'b' => [
                        'c' => '$another.path.component'
                    ]
                ],
                'expectedData' => [
                    'a' => ['prefix', 'path', 'component'],
                    'b' => [
                        'c' => ['prefix', 'another', 'path', 'component'],
                    ]
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
                    'a' => ['path', 'component'],
                    'b' => [
                        'c' => ['another', 'path', 'component'],
                    ]
                ],
                'prefix' => 'prefix'
            ],
        ];
    }
}
