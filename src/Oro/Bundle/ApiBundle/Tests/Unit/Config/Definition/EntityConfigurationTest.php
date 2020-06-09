<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config\Definition;

use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityConfiguration;
use Oro\Bundle\ApiBundle\Config\Definition\EntityDefinitionConfiguration;
use Oro\Bundle\ApiBundle\Config\Extension\ActionsConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Extension\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\SortersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\SubresourcesConfigExtension;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub\TestConfigExtension;
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
    /**
     * @dataProvider loadConfigurationDataProvider
     */
    public function testLoadConfiguration(array $config, array $expected, $error = null)
    {
        if (null !== $error) {
            $this->expectException(InvalidConfigurationException::class);
            $this->expectExceptionMessage($error);
        }

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

        $configuration = new EntityConfiguration(
            ApiConfiguration::ENTITIES_SECTION,
            new EntityDefinitionConfiguration(),
            $configExtensionRegistry->getConfigurationSettings(),
            1
        );
        $configBuilder = new TreeBuilder();
        $configuration->configure($configBuilder->root('entity')->children());

        $processor = new Processor();
        $result    = $processor->process($configBuilder->buildTree(), [$config]);

        if (null === $error) {
            self::assertEquals($expected, $result);
        }
    }

    public function loadConfigurationDataProvider()
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
