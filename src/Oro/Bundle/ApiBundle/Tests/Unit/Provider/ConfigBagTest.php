<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Request\Version;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

class ConfigBagTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigBag */
    protected $configBag;

    protected function setUp()
    {
        $config = [];
        foreach (['metadata', 'entities', 'relations'] as $section) {
            $config[$section] = [
                /* @todo: API version is not supported for now
                'Test\Class1' => [
                    '0' => ['fields' => ['class1_v0' => []]],
                ],
                'Test\Class2' => [
                    '0'   => ['fields' => ['class2_v0' => []]],
                    '1.0' => ['fields' => ['class2_v1.0' => []]],
                    '1.5' => ['fields' => ['class2_v1.5' => []]],
                    '2.0' => ['fields' => ['class2_v2.0' => []]],
                ],
                */
                'Test\Class1' => [
                    'fields' => ['class1_v0' => []],
                ],
                'Test\Class2' => [
                    'fields' => ['class2_v2.0' => []],
                ],
            ];
        }

        $this->configBag = new ConfigBag($config);
    }

    /**
     * @dataProvider getConfigsProvider
     */
    public function testGetConfigs($version, $expectedConfig)
    {
        $this->assertEquals(
            $expectedConfig,
            $this->configBag->getConfigs($version)
        );
    }

    public function getConfigsProvider()
    {
        return [
            /* @todo: API version is not supported for now. Add data to test versioning here */
            [
                '1.0',
                [
                    'Test\Class1' => ['fields' => ['class1_v0' => []]],
                    'Test\Class2' => ['fields' => ['class2_v2.0' => []]],
                ]
            ],
            [
                Version::LATEST,
                [
                    'Test\Class1' => ['fields' => ['class1_v0' => []]],
                    'Test\Class2' => ['fields' => ['class2_v2.0' => []]],
                ]
            ],
        ];
    }

    /**
     * @dataProvider noConfigProvider
     */
    public function testNoConfig($className, $version)
    {
        $this->assertNull(
            $this->configBag->getConfig($className, $version)
        );
    }

    /**
     * @dataProvider noConfigProvider
     */
    public function testNoRelationConfig($className, $version)
    {
        $this->assertNull(
            $this->configBag->getRelationConfig($className, $version)
        );
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
            $this->configBag->getConfig($className, $version)
        );
    }

    /**
     * @dataProvider getConfigProvider
     */
    public function testGetRelationConfig($className, $version, $expectedConfig)
    {
        $this->assertEquals(
            $expectedConfig,
            $this->configBag->getRelationConfig($className, $version)
        );
    }

    public function getConfigProvider()
    {
        return [
            /* @todo: API version is not supported for now
            ['Test\Class1', '0', ['fields' => ['class1_v0' => []]]],
            ['Test\Class1', '1.0', ['fields' => ['class1_v0' => []]]],
            ['Test\Class1', Version::LATEST, ['fields' => ['class1_v0' => []]]],
            ['Test\Class2', '0', ['fields' => ['class2_v0' => []]]],
            ['Test\Class2', '0.5', ['fields' => ['class2_v0' => []]]],
            ['Test\Class2', '1.0', ['fields' => ['class2_v1.0' => []]]],
            ['Test\Class2', '1.4', ['fields' => ['class2_v1.0' => []]]],
            ['Test\Class2', '1.5', ['fields' => ['class2_v1.5' => []]]],
            ['Test\Class2', '1.6', ['fields' => ['class2_v1.5' => []]]],
            ['Test\Class2', '2.0', ['fields' => ['class2_v2.0' => []]]],
            ['Test\Class2', '2.1', ['fields' => ['class2_v2.0' => []]]],
            ['Test\Class2', Version::LATEST, ['fields' => ['class2_v2.0' => []]]],
            */
            ['Test\Class1', '1.0', ['fields' => ['class1_v0' => []]]],
            ['Test\Class2', Version::LATEST, ['fields' => ['class2_v2.0' => []]]],
        ];
    }
}
