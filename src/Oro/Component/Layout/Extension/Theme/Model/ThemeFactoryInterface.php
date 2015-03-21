<?php

namespace Oro\Bundle\LayoutBundle\Theme;

use Oro\Bundle\LayoutBundle\Model\Theme;

interface ThemeFactoryInterface
{
    /**
     * @param string $themeName
     * @param array  $themeDefinition
     *
     * @return Theme
     */
    public function create($themeName, array $themeDefinition);
}
