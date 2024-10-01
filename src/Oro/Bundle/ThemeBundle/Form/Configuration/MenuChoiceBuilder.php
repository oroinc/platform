<?php

namespace Oro\Bundle\ThemeBundle\Form\Configuration;

use Oro\Bundle\NavigationBundle\Form\Type\MenuChoiceType;

/**
 * Builds menu choice type for theme configuration
 */
class MenuChoiceBuilder extends AbstractChoiceBuilder
{
    #[\Override]
    public static function getType(): string
    {
        return 'menu_selector';
    }

    #[\Override]
    protected function getTypeClass(): string
    {
        return MenuChoiceType::class;
    }

    #[\Override]
    protected function getDefaultOptions(): array
    {
        return [];
    }
}
