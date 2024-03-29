<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Oro\Bundle\NavigationBundle\Form\Type\MenuChoiceType;

/**
 * Builds menu choice type for theme configuration
 */
class MenuChoiceBuilder extends AbstractConfigurationChildBuilder
{
    #[\Override] public static function getType(): string
    {
        return 'menu_selector';
    }

    #[\Override] public function supports(array $option): bool
    {
        return $option['type'] === self::getType();
    }

    #[\Override] protected function getTypeClass(): string
    {
        return MenuChoiceType::class;
    }

    #[\Override] protected function getDefaultOptions(): array
    {
        return [];
    }
}
