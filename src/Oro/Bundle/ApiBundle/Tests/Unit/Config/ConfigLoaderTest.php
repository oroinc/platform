<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub\TestConfigLoader;

class ConfigLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testLoaders($configType, $config, $expected)
    {
        $configLoaderFactory = new ConfigLoaderFactory();
        $configLoaderFactory->setLoader('test_section', new TestConfigLoader());

        $result = $configLoaderFactory->getLoader($configType)->load($config);
        $this->assertEquals($expected, $result->toArray());
    }

    public function dataProvider()
    {
        $result = [];

        $finder = new Finder();
        $finder
            ->files()
            ->in(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'Loader')
            ->name('*.yml');
        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $configType = substr($file->getFilename(), 0, -4);
            $data       = Yaml::parse($file->getContents());
            foreach ($data as $testName => $testData) {
                $result[$configType . '_' . $testName] = [
                    'configType' => $configType,
                    'config'     => $testData['config'],
                    'expected'   => $testData['expected']
                ];
            }
        }

        return $result;
    }
}
