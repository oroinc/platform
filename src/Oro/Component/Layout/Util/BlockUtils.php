<?php

namespace Oro\Component\Layout\Util;

use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockView;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * The set of static methods to help working with layout blocks.
 */
class BlockUtils
{
    private const UNIQUE_BLOCK_PREFIX_PATTERN = '/[^a-z0-9\_]+/i';

    /**
     * Registers the plugin for the block type.
     * You can use this method to add the additional block prefix that allow you
     * to create an additional template for existing block type.
     *
     * IMPORTANT: This method should be called in finishView of your block type extension
     * because the 'block_prefixes' array is not filled in buildView yet.
     *
     * @param BlockView $view
     * @param string    $pluginName
     */
    public static function registerPlugin(BlockView $view, $pluginName)
    {
        $optionsArray = $view->vars['block_prefixes'];
        array_splice(
            $optionsArray,
            -1,
            1,
            [$pluginName, end($optionsArray)]
        );
        $view->vars['block_prefixes'] = $optionsArray;
    }

    /**
     * Normalizes the given value to the format that can be translated by a renderer.
     *
     * @param string|array $text       The text to be translated
     * @param array|null   $parameters The parameters
     *
     * @return array
     */
    public static function normalizeTransValue($text, $parameters = null)
    {
        if (is_string($text) && !empty($text)) {
            $text = ['label' => $text];
        }
        if (!empty($parameters) && is_array($text) && !isset($text['parameters'])) {
            $text['parameters'] = $parameters;
        }

        return $text;
    }

    /**
     * Gets the url related options and pass them to the block view.
     *
     * @param BlockView   $view     The block view
     * @param Options     $options  The block options
     * @param boolean     $required Specifies whether the url related options are mandatory
     * @param string|null $prefix   The prefix for the url related options
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public static function processUrl(BlockView $view, Options $options, $required = false, $prefix = null)
    {
        $pathName  = null !== $prefix ? $prefix . '_path' : 'path';
        $routeName = null !== $prefix ? $prefix . '_route_name' : 'route_name';
        if ($options->isExistsAndNotEmpty($pathName)) {
            $view->vars[$pathName] = $options[$pathName];
        } elseif ($options->isExistsAndNotEmpty($routeName)) {
            $view->vars[$routeName] = $options[$routeName];

            $routeParamName = null !== $prefix ? $prefix . '_route_parameters' : 'route_parameters';
            $view->vars[$routeParamName] = $options[$routeParamName] ?? [];
        } elseif ($required) {
            throw new MissingOptionsException(
                sprintf('Either "%s" or "%s" must be set.', $pathName, $routeName)
            );
        }
    }

    public static function setViewVarsFromOptions(BlockView $view, Options $options, array $optionNames): void
    {
        foreach ($optionNames as $optionName) {
            $view->vars[$optionName] = isset($options[$optionName]) ? $options->get($optionName, false) : null;
        }
    }

    public static function populateComputedViewVars(array &$vars, string $contextHash): void
    {
        $id = $vars['id'];
        $type = $vars['block_type'];
        $vars['block_type_widget_id'] = $type . '_widget';
        $vars['unique_block_prefix']  = '_' . preg_replace(self::UNIQUE_BLOCK_PREFIX_PATTERN, '_', $id);
        $vars['cache_key'] = sprintf('_%s_%s_%s', $id, $type, $contextHash);
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}
