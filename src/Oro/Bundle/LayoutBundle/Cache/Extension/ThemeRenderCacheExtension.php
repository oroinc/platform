<?php

namespace Oro\Bundle\LayoutBundle\Cache\Extension;

use Oro\Component\Layout\LayoutContextStack;

/**
 * Render cache extension that adds theme to varyBy cache metadata.
 */
class ThemeRenderCacheExtension implements RenderCacheExtensionInterface
{
    private LayoutContextStack $layoutContextStack;

    public function __construct(LayoutContextStack $layoutContextStack)
    {
        $this->layoutContextStack = $layoutContextStack;
    }

    public function alwaysVaryBy(): array
    {
        $context = $this->layoutContextStack->getCurrentContext();

        return $context ? ['theme' => $context->get('theme')] : [];
    }
}
