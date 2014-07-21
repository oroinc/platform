<?php

namespace Oro\Bundle\NavigationBundle\ContentProvider;

use Oro\Bundle\NavigationBundle\Twig\MenuExtension;
use Oro\Bundle\UIBundle\ContentProvider\AbstractContentProvider;

class MenuContentProvider extends AbstractContentProvider
{
    /**
     * @var MenuExtension
     */
    protected $menuExtension;

    /**
     * @var string
     */
    protected $menu;

    /**
     * @var string
     */
    protected $name;

    /**
     * @param MenuExtension $menuExtension
     * @param string $menu
     * @param string $name
     */
    public function __construct(MenuExtension $menuExtension, $menu, $name)
    {
        $this->menuExtension = $menuExtension;
        $this->menu = $menu;
        $this->name = $name;
    }

    /**
     * {@inheritDoc}
     */
    public function getContent()
    {
        return $this->menuExtension->render($this->menu);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }
}
