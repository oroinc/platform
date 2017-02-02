<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

class ThemeFactory implements ThemeFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function create($themeName, array $themeDefinition)
    {
        $theme = new Theme(
            $themeName,
            isset($themeDefinition['parent']) ? $themeDefinition['parent'] : null
        );

        $this->applyThemeProperties($theme, $themeDefinition);

        if (isset($themeDefinition['config'])) {
            $theme->setConfig($themeDefinition['config']);
        }

        if (isset($themeDefinition['config']['page_templates']['templates'])) {
            foreach ($themeDefinition['config']['page_templates']['templates'] as $pageTemplateConfig) {
                $pageTemplate = new PageTemplate(
                    $pageTemplateConfig['label'],
                    $pageTemplateConfig['key'],
                    $pageTemplateConfig['route_name']
                );
                if (isset($pageTemplateConfig['description'])) {
                    $pageTemplate->setDescription($pageTemplateConfig['description']);
                }
                if (isset($pageTemplateConfig['screenshot'])) {
                    $pageTemplate->setScreenshot($pageTemplateConfig['screenshot']);
                }

                $theme->addPageTemplate($pageTemplate);
            }
        }

        return $theme;
    }

    /**
     * @param Theme $theme
     * @param array $themeDefinition
     */
    private function applyThemeProperties(Theme $theme, array $themeDefinition)
    {
        if (isset($themeDefinition['label'])) {
            $theme->setLabel($themeDefinition['label']);
        }
        if (isset($themeDefinition['screenshot'])) {
            $theme->setScreenshot($themeDefinition['screenshot']);
        }
        if (isset($themeDefinition['icon'])) {
            $theme->setIcon($themeDefinition['icon']);
        }
        if (isset($themeDefinition['logo'])) {
            $theme->setLogo($themeDefinition['logo']);
        }
        if (isset($themeDefinition['directory'])) {
            $theme->setDirectory($themeDefinition['directory']);
        }
        if (isset($themeDefinition['groups'])) {
            $theme->setGroups((array)$themeDefinition['groups']);
        }
        if (isset($themeDefinition['description'])) {
            $theme->setDescription($themeDefinition['description']);
        }
    }
}
