<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Configuration;

use Oro\Bundle\SearchBundle\Configuration\MappingConfiguration;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class MappingConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider processConfigurationDataProvider
     */
    public function testProcessConfiguration(array $configs, array $expected)
    {
        $processor = new Processor();

        $this->assertEquals(
            $expected,
            $processor->processConfiguration(new MappingConfiguration(), $configs)
        );
    }

    public function processConfigurationDataProvider(): array
    {
        $cases = [];
        $finder = (new Finder())->files()->in(__DIR__ . '/mapping_configuration')->name('*.yml');
        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            $cases[$file->getRelativePathname()] = Yaml::parse($file->getContents());
        }

        return $cases;
    }
}
