<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension\Generator;

use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGeneratorExtensionInterface;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

class ThemesRelativePathGeneratorExtension implements ConfigLayoutUpdateGeneratorExtensionInterface
{
    const NODE_ACTIONS = 'actions';
    const NODE_THEMES = 'themes';
    const ACTION_SET_FORM_THEME_TREE = '@setFormTheme';
    const ACTION_SET_BLOCK_THEME_TREE = '@setBlockTheme';

    /**
     * {@inheritdoc}
     */
    public function prepare(GeneratorData $data, VisitorCollection $collection)
    {
        $source = $data->getSource();
        $file = $data->getFilename();

        if (is_array($source) && is_string($file) && isset($source[self::NODE_ACTIONS])) {
            $this->each($source[self::NODE_ACTIONS], function (&$actionDefinition) use ($file) {
                $actionName = is_array($actionDefinition) ? key($actionDefinition) : '';
                if (in_array($actionName, [self::ACTION_SET_BLOCK_THEME_TREE, self::ACTION_SET_FORM_THEME_TREE], true)
                    && array_key_exists(self::NODE_THEMES, $actionDefinition[$actionName])
                ) {
                    $themes = $actionDefinition[$actionName][self::NODE_THEMES];
                    $themes = $themes === null ? [null] : (array)$themes;
                    $this->each($themes, function (&$theme) use ($file) {
                        $theme = $this->prepareThemePath($theme, $file);
                    });
                    if (count($themes) === 1) {
                        $themes = reset($themes);
                    }
                    $actionDefinition[$actionName][self::NODE_THEMES] = $themes;
                }
            });
            $data->setSource($source);
        }
    }

    /**
     * @param mixed $value
     * @param callable $callable
     */
    protected function each(&$value, callable $callable)
    {
        if (is_array($value)) {
            foreach ($value as &$childValue) {
                $callable($childValue);
            }
        }
    }

    /**
     * @param string $theme
     * @param string $file
     * @return string
     */
    protected function prepareThemePath($theme, $file)
    {
        $relativePath = null;
        if ($theme === null) {
            $relativePath = basename($file, '.yml').'.html.twig';
        } elseif (strpos($theme, ':') === false && strpos($theme, '/') !== 0) {
            $relativePath = $theme;
        }
        if ($relativePath) {
            $absolutePath = realpath(dirname($file).'/'.$relativePath);
            if ($absolutePath) {
                return $absolutePath;
            }
        }
        return $theme;
    }
}
