<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension\Generator;

use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGenerator;
use Oro\Component\Layout\Loader\Generator\ConfigLayoutUpdateGeneratorExtensionInterface;
use Oro\Component\Layout\Loader\Generator\GeneratorData;
use Oro\Component\Layout\Loader\Visitor\VisitorCollection;

class ThemesRelativePathGeneratorExtension implements ConfigLayoutUpdateGeneratorExtensionInterface
{
    const THEMES_KEY = 'themes';
    const ACTION_SET_FORM_THEME = '@setFormTheme';
    const ACTION_SET_BLOCK_THEME = '@setBlockTheme';

    /**
     * {@inheritdoc}
     */
    public function prepare(GeneratorData $data, VisitorCollection $collection)
    {
        $source = $data->getSource();
        $file = $data->getFilename();

        $actionsKey = ConfigLayoutUpdateGenerator::NODE_ACTIONS;
        if (is_array($source) && $file && array_key_exists($actionsKey, $source)) {
            $source[$actionsKey] = array_map(function ($actionDefinition) use ($file) {
                $actionName = is_array($actionDefinition) ? key($actionDefinition) : '';
                if (in_array($actionName, [self::ACTION_SET_BLOCK_THEME, self::ACTION_SET_FORM_THEME], true)
                    && array_key_exists(self::THEMES_KEY, $actionDefinition[$actionName])
                ) {
                    $themes = array_map(function ($theme) use ($file) {
                        return $this->prepareThemePath($theme, $file);
                    }, (array)$actionDefinition[$actionName][self::THEMES_KEY]);
                    if (count($themes) === 1) {
                        $themes = reset($themes);
                    }
                    $actionDefinition[$actionName][self::THEMES_KEY] = $themes;
                }
                return $actionDefinition;
            }, $source[$actionsKey]);
            $data->setSource($source);
        }
    }

    /**
     * @param string $theme
     * @param string $file
     * @return string
     */
    protected function prepareThemePath($theme, $file)
    {
        if ($theme && strpos($theme, ':') === false && strpos($theme, '/') !== 0 && strpos($theme, '@') !== 0) {
            $absolutePath = realpath(dirname($file).'/'.$theme);
            if ($absolutePath) {
                return $absolutePath;
            }
        }
        return $theme;
    }
}
