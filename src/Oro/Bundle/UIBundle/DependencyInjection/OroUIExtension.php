<?php

namespace Oro\Bundle\UIBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OroUIExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        array_unshift(
            $configs,
            array('placeholders_items' => $this->loadPlaceholdersConfigs($container))
        );
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.yml');
        $loader->load('twig.yml');

        $container->setParameter('oro_ui.show_pin_button_on_start_page', $config['show_pin_button_on_start_page']);
        $container->setParameter('oro_ui.placeholders', $config['placeholders_items']);

        $container->prependExtensionConfig($this->getAlias(), array_intersect_key($config, array_flip(['settings'])));
    }

    /**
     * Loads configuration from placeholders.yml files
     *
     * @param ContainerBuilder $container
     * @return array
     */
    protected function loadPlaceholdersConfigs(ContainerBuilder $container)
    {
        $placeholders = array();
        $items        = array();

        $configLoader = new CumulativeConfigLoader(
            'oro_placeholders',
            new YamlCumulativeFileLoader('Resources/config/placeholders.yml')
        );
        $resources    = $configLoader->load($container);
        foreach ($resources as $resource) {
            if (isset($resource->data['placeholders'])) {
                $this->ensurePlaceholdersCompleted($resource->data['placeholders']);
                $placeholders = array_replace_recursive($placeholders, $resource->data['placeholders']);
            }
            if (isset($resource->data['items'])) {
                $items = array_replace_recursive($items, $resource->data['items']);
            }
        }

        $this->addItemsToPlaceholders($placeholders, $items);

        return $placeholders;
    }

    /**
     * Insert items data (templates, actions, etc) into placeholders item data
     *
     * @param array $placeholders
     * @param array $items
     */
    protected function addItemsToPlaceholders(array &$placeholders, array $items)
    {
        foreach ($placeholders as $placeholderName => $placeholder) {
            if (isset($placeholder['items']) && !empty($placeholder['items'])) {
                foreach ($placeholder['items'] as $itemName => $itemData) {
                    if (!isset($items[$itemName])) {
                        // remove a link to undefined item
                        unset($placeholders[$placeholderName]['items'][$itemName]);
                    } elseif (empty($itemData)) {
                        // copy item attributes defined inside 'items' section to 'placeholders' section
                        $placeholders[$placeholderName]['items'][$itemName] = $items[$itemName];
                    } else {
                        // merge item attributes defined inside 'placeholders' and 'items' sections
                        $placeholders[$placeholderName]['items'][$itemName] = array_merge(
                            $items[$itemName],
                            $itemData
                        );
                    }
                }
            }
        }
    }

    /**
     * Makes sure the placeholder's array does not contains gaps
     *
     * For example 'items' attribute should exist for each placeholder
     * even if there are no any items there
     *
     * it is required for correct merging of placeholders
     * if we do not do this the newly loaded placeholder without 'items' attribute removes
     * already loaded items
     *
     * @param array $placeholders
     */
    protected function ensurePlaceholdersCompleted(&$placeholders)
    {
        $names = array_keys($placeholders);
        foreach ($names as $name) {
            if (!isset($placeholders[$name]['items'])) {
                $placeholders[$name]['items'] = array();
            }
        }
    }
}
