<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Definition;

use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityDefinitionConfiguration;
use Oro\Bundle\ApiBundle\Tests\Unit\Config\ConfigExtensionRegistryTrait;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

/**
 * Tests extensions config tree definitions
 */
class EntityConfigurationTest extends \PHPUnit\Framework\TestCase
{
    use ConfigExtensionRegistryTrait;

    /**
     * @dataProvider loadConfigurationDataProvider
     */
    public function testLoadConfiguration(array $config, array $expected, string $error = null)
    {
        if (null !== $error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage($error);
        }

        $configExtensionRegistry = $this->createConfigExtensionRegistry();
        $configuration = new EntityConfiguration(
            ApiConfiguration::ENTITIES_SECTION,
            new EntityDefinitionConfiguration(),
            $configExtensionRegistry->getConfigurationSettings(),
            1
        );
        $configBuilder = new TreeBuilder('entity');
        $configuration->configure($configBuilder->getRootNode()->children());

        $processor = new Processor();
        $result = $processor->process($configBuilder->buildTree(), [$config]);

        if (null === $error) {
            self::assertEquals($expected, $result);
        }
    }

    public function loadConfigurationDataProvider(): array
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
                $result[$configType . '_' . $testName] = $testData;
            }
        }

        return $result;
    }
}
