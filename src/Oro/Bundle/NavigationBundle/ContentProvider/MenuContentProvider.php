<?php

namespace Oro\Bundle\NavigationBundle\ContentProvider;

use Oro\Bundle\NavigationBundle\Twig\MenuExtension;
use Oro\Bundle\UIBundle\ContentProvider\ContentProviderInterface;

/**
 * Renders a specific menu item.
 */
class MenuContentProvider implements ContentProviderInterface
{
    /** @var MenuExtension */
    private $menuExtension;

    /** @var string */
    private $menu;

    public function __construct(MenuExtension $menuExtension, string $menu)
    {
        $this->menuExtension = $menuExtension;
        $this->menu = $menu;
    }

    /**
     * {@inheritDoc}
     */
    public function getContent()
    {
        return $this->menuExtension->render($this->menu);
    }
}
