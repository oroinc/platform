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

        $this->addPageTemplatesConfig($themeDefinition, $theme);

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

    /**
     * @param array $themeDefinition
     * @param Theme $theme
     */
    private function addPageTemplatesConfig(array $themeDefinition, Theme $theme)
    {
        if (isset($themeDefinition['config']['page_templates']['titles'])) {
            foreach ($themeDefinition['config']['page_templates']['titles'] as $routeKey => $title) {
                $theme->addPageTemplateTitle($routeKey, $title);
            }
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
                if (isset($pageTemplateConfig['enabled'])) {
                    $pageTemplate->setEnabled($pageTemplateConfig['enabled']);
                }

                $theme->addPageTemplate($pageTemplate);
            }
        }
    }
}
