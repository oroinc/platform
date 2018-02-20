<?php

namespace Oro\Component\Layout\Util;

use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockView;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class BlockUtils
{
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

            $routeParamName              = null !== $prefix ? $prefix . '_route_parameters' : 'route_parameters';
            $view->vars[$routeParamName] = isset($options[$routeParamName])
                ? $options[$routeParamName]
                : [];
        } elseif ($required) {
            throw new MissingOptionsException(
                sprintf('Either "%s" or "%s" must be set.', $pathName, $routeName)
            );
        }
    }

    /**
     * @param BlockView $view
     * @param Options   $options
     * @param array     $optionNames
     */
    public static function setViewVarsFromOptions(BlockView $view, Options $options, array $optionNames)
    {
        foreach ($optionNames as $optionName) {
            $view->vars[$optionName] = isset($options[$optionName]) ? $options->get($optionName, false) : null;
        }
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}
