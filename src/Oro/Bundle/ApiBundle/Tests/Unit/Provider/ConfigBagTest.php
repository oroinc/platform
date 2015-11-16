<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Request\Version;

class ConfigBagTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigBag */
    protected $configBag;

    protected function setUp()
    {
        $config = [
            'entities'  => [
                'Test\Class1' => [
                    '0' => ['definition' => ['fields' => ['class1_v0' => []]]],
                ],
                'Test\Class2' => [
                    '0'   => ['definition' => ['fields' => ['class2_v0' => []]]],
                    '1.0' => ['definition' => ['fields' => ['class2_v1.0' => []]]],
                    '1.5' => ['definition' => ['fields' => ['class2_v1.5' => []]]],
                    '2.0' => ['definition' => ['fields' => ['class2_v2.0' => []]]],
                ],
            ],
            'relations' => [
                'Test\Class1' => [
                    '0' => ['definition' => ['fields' => ['class1_v0' => []]]],
                ],
                'Test\Class2' => [
                    '0'   => ['definition' => ['fields' => ['class2_v0' => []]]],
                    '1.0' => ['definition' => ['fields' => ['class2_v1.0' => []]]],
                    '1.5' => ['definition' => ['fields' => ['class2_v1.5' => []]]],
                    '2.0' => ['definition' => ['fields' => ['class2_v2.0' => []]]],
                ],
            ]
        ];

        $this->configBag = new ConfigBag($config);
    }

    /**
     * @dataProvider noConfigProvider
     */
    public function testNoConfig($className, $version)
    {
        $this->assertNull($this->configBag->getConfig($className, $version));
    }

    public function noConfigProvider()
    {
        return [
            ['Test\UnknownClass', Version::LATEST]
        ];
    }

    /**
     * @dataProvider getConfigProvider
     */
    public function testGetConfig($className, $version, $expectedConfig)
    {
        $this->assertEquals(
            $expectedConfig,
            $this->configBag->getConfig($className, $version),
            'getConfig'
        );
        $this->assertEquals(
            $expectedConfig,
            $this->configBag->getRelationConfig($className, $version),
            'getRelationConfig'
        );
    }

    public function getConfigProvider()
    {
        return [
            ['Test\Class1', '0', ['definition' => ['fields' => ['class1_v0' => []]]]],
            ['Test\Class1', '1.0', ['definition' => ['fields' => ['class1_v0' => []]]]],
            ['Test\Class1', Version::LATEST, ['definition' => ['fields' => ['class1_v0' => []]]]],
            ['Test\Class2', '0', ['definition' => ['fields' => ['class2_v0' => []]]]],
            ['Test\Class2', '0.5', ['definition' => ['fields' => ['class2_v0' => []]]]],
            ['Test\Class2', '1.0', ['definition' => ['fields' => ['class2_v1.0' => []]]]],
            ['Test\Class2', '1.4', ['definition' => ['fields' => ['class2_v1.0' => []]]]],
            ['Test\Class2', '1.5', ['definition' => ['fields' => ['class2_v1.5' => []]]]],
            ['Test\Class2', '1.6', ['definition' => ['fields' => ['class2_v1.5' => []]]]],
            ['Test\Class2', '2.0', ['definition' => ['fields' => ['class2_v2.0' => []]]]],
            ['Test\Class2', '2.1', ['definition' => ['fields' => ['class2_v2.0' => []]]]],
            ['Test\Class2', Version::LATEST, ['definition' => ['fields' => ['class2_v2.0' => []]]]],
        ];
    }
}
