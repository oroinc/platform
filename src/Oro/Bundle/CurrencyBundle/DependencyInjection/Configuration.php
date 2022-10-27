<?php

namespace Oro\Bundle\CurrencyBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\CurrencyBundle\Provider\ViewTypeProviderInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const DEFAULT_CURRENCY = 'USD';
    const DEFAULT_VIEW = ViewTypeProviderInterface::VIEW_TYPE_SYMBOL;

    const ROOT_NAME = 'oro_currency';
    const KEY_DEFAULT_CURRENCY = 'default_currency';
    const KEY_CURRENCY_DISPLAY = 'currency_display';

    /**
     * Returns full key name by it's last part
     *
     * @param $name string last part of the key name (one of the class cons can be used)
     * @return string full config path key
     */
    public static function getConfigKeyByName($name)
    {
        return self::ROOT_NAME . ConfigManager::SECTION_MODEL_SEPARATOR . $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(self::ROOT_NAME);

        $rootNode = $treeBuilder->getRootNode();

        SettingsBuilder::append(
            $rootNode,
            [
                self::KEY_DEFAULT_CURRENCY   => ['value' => self::DEFAULT_CURRENCY, 'type' => 'scalar'],
                self::KEY_CURRENCY_DISPLAY => ['value' => self::DEFAULT_VIEW, 'type' => 'scalar'],
            ]
        );

        return $treeBuilder;
    }
}
