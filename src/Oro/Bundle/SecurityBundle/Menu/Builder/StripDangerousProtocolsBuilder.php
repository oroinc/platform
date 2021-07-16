<?php

namespace Oro\Bundle\SecurityBundle\Menu\Builder;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Menu\BuilderInterface;
use Oro\Bundle\SecurityBundle\Util\UriSecurityHelper;

/**
 * Strips dangerous protocols from menu items URIs.
 */
class StripDangerousProtocolsBuilder implements BuilderInterface
{
    /** @var UriSecurityHelper */
    private $uriSecurityHelper;

    public function __construct(UriSecurityHelper $uriSecurityHelper)
    {
        $this->uriSecurityHelper = $uriSecurityHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ItemInterface $menu, array $options = [], $alias = null): void
    {
        $this->stripRecursively($menu);
    }

    private function stripRecursively(ItemInterface $menu): void
    {
        $menuChildren = $menu->getChildren();

        foreach ($menuChildren as $menuChild) {
            $this->stripRecursively($menuChild);
        }

        $uri = $menu->getUri();
        if ($uri) {
            $menu->setUri($this->uriSecurityHelper->stripDangerousProtocols($uri));
        }
    }
}
