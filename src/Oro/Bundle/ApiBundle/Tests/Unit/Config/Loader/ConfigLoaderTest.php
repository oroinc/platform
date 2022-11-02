<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Loader;

use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Tests\Unit\Config\ConfigExtensionRegistryTrait;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class ConfigLoaderTest extends \PHPUnit\Framework\TestCase
{
    use ConfigExtensionRegistryTrait;

    /**
     * @dataProvider dataProvider
     */
    public function testLoaders(string $configType, array $config, array $expected)
    {
        $configLoaderFactory = new ConfigLoaderFactory($this->createConfigExtensionRegistry());

        $result = $configLoaderFactory->getLoader($configType)->load($config);
        self::assertEquals($expected, $result->toArray());
    }

    public function dataProvider(): array
    {
        $result = [];

        $finder = new Finder();
        $finder
            ->files()
            ->in(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures')
            ->name('*.yml');
        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $configType = substr($file->getFilename(), 0, -4);
            $data = Yaml::parse($file->getContents());
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
