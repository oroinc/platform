<?php

namespace Oro\Component\Layout\Util;

use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

use Oro\Component\Layout\BlockView;

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
        array_splice(
            $view->vars['block_prefixes'],
            -1,
            1,
            [$pluginName, end($view->vars['block_prefixes'])]
        );
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
     * @param array       $options  The block options
     * @param boolean     $required Specifies whether the url related options are mandatory
     * @param string|null $prefix   The prefix for the url related options
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public static function processUrl(BlockView $view, array $options, $required = false, $prefix = null)
    {
        $pathName  = null !== $prefix ? $prefix . '_path' : 'path';
        $routeName = null !== $prefix ? $prefix . '_route_name' : 'route_name';
        if (!empty($options[$pathName])) {
            $view->vars[$pathName] = $options[$pathName];
        } elseif (!empty($options[$routeName])) {
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
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}
