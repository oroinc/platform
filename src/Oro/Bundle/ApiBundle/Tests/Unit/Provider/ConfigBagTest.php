<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Provider;

use Oro\Bundle\ApiBundle\Provider\ConfigBag;
use Oro\Bundle\ApiBundle\Request\Version;

class ConfigBagTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigBag */
    private $configBag;

    protected function setUp()
    {
        $config = [];
        foreach (['metadata', 'entities', 'relations'] as $section) {
            $config[$section] = [
                'Test\Class1' => [
                    'fields' => ['class1_v0' => []]
                ],
                'Test\Class2' => [
                    'fields' => ['class2_v2.0' => []]
                ]
            ];
        }

        $this->configBag = new ConfigBag($config);
    }

    /**
     * @dataProvider getClassNamesProvider
     */
    public function testGetClassNames($version, $expectedEntityClasses)
    {
        self::assertEquals(
            $expectedEntityClasses,
            $this->configBag->getClassNames($version)
        );
    }

    public function getClassNamesProvider()
    {
        return [
            [
                Version::LATEST,
                ['Test\Class1', 'Test\Class2']
            ]
        ];
    }

    /**
     * @dataProvider noConfigProvider
     */
    public function testNoConfig($className, $version)
    {
        self::assertNull(
            $this->configBag->getConfig($className, $version)
        );
    }

    /**
     * @dataProvider noConfigProvider
     */
    public function testNoRelationConfig($className, $version)
    {
        self::assertNull(
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
        self::assertEquals(
            $expectedConfig,
            $this->configBag->getConfig($className, $version)
        );
    }

    /**
     * @dataProvider getConfigProvider
     */
    public function testGetRelationConfig($className, $version, $expectedConfig)
    {
        self::assertEquals(
            $expectedConfig,
            $this->configBag->getRelationConfig($className, $version)
        );
    }

    public function getConfigProvider()
    {
        return [
            ['Test\Class1', '1.0', ['fields' => ['class1_v0' => []]]],
            ['Test\Class2', Version::LATEST, ['fields' => ['class2_v2.0' => []]]]
        ];
    }
}
