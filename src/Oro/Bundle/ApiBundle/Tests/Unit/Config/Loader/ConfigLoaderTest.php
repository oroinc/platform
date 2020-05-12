<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Loader;

use Oro\Bundle\ApiBundle\Config\Extension\ActionsConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Extension\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\SortersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\SubresourcesConfigExtension;
use Oro\Bundle\ApiBundle\Config\Loader\ConfigLoaderFactory;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Request\ApiAction;
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
                ApiAction::GET,
                ApiAction::GET_LIST,
                ApiAction::UPDATE,
                ApiAction::CREATE,
                ApiAction::DELETE,
                ApiAction::DELETE_LIST,
                ApiAction::GET_SUBRESOURCE,
                ApiAction::GET_RELATIONSHIP,
                ApiAction::UPDATE_RELATIONSHIP,
                ApiAction::ADD_RELATIONSHIP,
                ApiAction::DELETE_RELATIONSHIP
            ]);
        $filterOperatorRegistry = new FilterOperatorRegistry([
            FilterOperator::EQ              => '=',
            FilterOperator::NEQ             => '!=',
            FilterOperator::GT              => '>',
            FilterOperator::LT              => '<',
            FilterOperator::GTE             => '>=',
            FilterOperator::LTE             => '<=',
            FilterOperator::EXISTS          => '*',
            FilterOperator::NEQ_OR_NULL     => '!*',
            FilterOperator::CONTAINS        => '~',
            FilterOperator::NOT_CONTAINS    => '!~',
            FilterOperator::STARTS_WITH     => '^',
            FilterOperator::NOT_STARTS_WITH => '!^',
            FilterOperator::ENDS_WITH       => '$',
            FilterOperator::NOT_ENDS_WITH   => '!$'
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
