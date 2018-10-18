<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\ActionsConfigExtension;
use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Config\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\SortersConfigExtension;
use Oro\Bundle\ApiBundle\Config\SubresourcesConfigExtension;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Request\ApiActions;
use Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub\TestConfigExtension;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Yaml\Yaml;

class ConfigLoaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testLoaders($configType, $config, $expected)
    {
        $actionProcessorBag = $this->createMock(ActionProcessorBagInterface::class);
        $actionProcessorBag->expects(self::any())
            ->method('getActions')
            ->willReturn([
                ApiActions::GET,
                ApiActions::GET_LIST,
                ApiActions::UPDATE,
                ApiActions::CREATE,
                ApiActions::DELETE,
                ApiActions::DELETE_LIST,
                ApiActions::GET_SUBRESOURCE,
                ApiActions::GET_RELATIONSHIP,
                ApiActions::UPDATE_RELATIONSHIP,
                ApiActions::ADD_RELATIONSHIP,
                ApiActions::DELETE_RELATIONSHIP
            ]);
        $filterOperatorRegistry = new FilterOperatorRegistry([
            ComparisonFilter::EQ              => '=',
            ComparisonFilter::NEQ             => '!=',
            ComparisonFilter::GT              => '>',
            ComparisonFilter::LT              => '<',
            ComparisonFilter::GTE             => '>=',
            ComparisonFilter::LTE             => '<=',
            ComparisonFilter::EXISTS          => '*',
            ComparisonFilter::NEQ_OR_NULL     => '!*',
            ComparisonFilter::CONTAINS        => '~',
            ComparisonFilter::NOT_CONTAINS    => '!~',
            ComparisonFilter::STARTS_WITH     => '^',
            ComparisonFilter::NOT_STARTS_WITH => '!^',
            ComparisonFilter::ENDS_WITH       => '$',
            ComparisonFilter::NOT_ENDS_WITH   => '!$'
        ]);

        $configExtensionRegistry = new ConfigExtensionRegistry();
        $configExtensionRegistry->addExtension(new FiltersConfigExtension($filterOperatorRegistry));
        $configExtensionRegistry->addExtension(new SortersConfigExtension());
        $configExtensionRegistry->addExtension(new ActionsConfigExtension($actionProcessorBag));
        $configExtensionRegistry->addExtension(
            new SubresourcesConfigExtension($actionProcessorBag, $filterOperatorRegistry)
        );
        $configExtensionRegistry->addExtension(new TestConfigExtension());

        $configLoaderFactory = new ConfigLoaderFactory($configExtensionRegistry);

        $result = $configLoaderFactory->getLoader($configType)->load($config);
        self::assertEquals($expected, $result->toArray());
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
