<?php

namespace Oro\Bundle\LayoutBundle\Tests\Functional\Stub\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationProvider;

class ThemeConfigurationTestProvider extends ThemeConfigurationProvider
{
    private const THEME_DEFINITIONS = [
        'base' => [
            'label' => 'base'
        ],
        'nested_imports' => [
            'parent' => 'base',
            'label' => 'nested_imports'
        ],
        'nested_imports_with_conditions' => [
            'parent' => 'base',
            'label' => 'nested_imports_with_conditions'
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getThemeNames(): array
    {
        return array_merge(
            array_keys(self::THEME_DEFINITIONS),
            parent::getThemeNames()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getThemeDefinition(string $themeName): ?array
    {
        return self::THEME_DEFINITIONS[$themeName] ?? parent::getThemeDefinition($themeName);
    }
}
