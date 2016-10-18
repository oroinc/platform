<?php

namespace Oro\Bundle\CurrencyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{

    const DEFAULT_CURRENCY = 'USD';
    const DEFAULT_VIEW     = 'iso_code';

    /**
     * @deprecated
     * @var array
     */
    public static $defaultCurrencies = [self::DEFAULT_CURRENCY];

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('oro_currency');

        SettingsBuilder::append(
            $rootNode,
            [
                'default_currency'   => ['value' => self::DEFAULT_CURRENCY, 'type' => 'scalar'],
                'currency_display'   => ['value' => self::DEFAULT_VIEW, 'type' => 'scalar'],
            ]
        );

        return $treeBuilder;
    }
}
