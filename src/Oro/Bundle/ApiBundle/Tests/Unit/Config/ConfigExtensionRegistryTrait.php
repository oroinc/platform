<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Config;

use Oro\Bundle\ApiBundle\Config\Extension\ActionsConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Extension\FiltersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\PostProcessorConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\SortersConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\SubresourcesConfigExtension;
use Oro\Bundle\ApiBundle\Config\Extension\TwigPostProcessorConfigExtension;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterOperatorRegistry;
use Oro\Bundle\ApiBundle\PostProcessor\PostProcessorRegistry;
use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Unit\Config\Stub\TestConfigExtension;

trait ConfigExtensionRegistryTrait
{
    private function createConfigExtensionRegistry(): ConfigExtensionRegistry
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
            FilterOperator::NOT_ENDS_WITH   => '!$',
            FilterOperator::EMPTY_VALUE     => null
        ]);
        $postProcessorRegistry = $this->createMock(PostProcessorRegistry::class);
        $postProcessorRegistry->expects(self::any())
            ->method('getPostProcessorNames')
            ->willReturn(['twig', 'test']);

        $configExtensionRegistry = new ConfigExtensionRegistry();
        $configExtensionRegistry->addExtension(new FiltersConfigExtension($filterOperatorRegistry));
        $configExtensionRegistry->addExtension(new SortersConfigExtension());
        $configExtensionRegistry->addExtension(new ActionsConfigExtension($actionProcessorBag));
        $configExtensionRegistry->addExtension(new SubresourcesConfigExtension(
            $actionProcessorBag,
            $filterOperatorRegistry
        ));
        $configExtensionRegistry->addExtension(new PostProcessorConfigExtension($postProcessorRegistry));
        $configExtensionRegistry->addExtension(new TwigPostProcessorConfigExtension());
        $configExtensionRegistry->addExtension(new TestConfigExtension());

        return $configExtensionRegistry;
    }
}
