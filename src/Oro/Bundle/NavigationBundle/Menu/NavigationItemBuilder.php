<?php

namespace Oro\Bundle\NavigationBundle\Menu;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Provider\NavigationItemsProviderInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Builds menu from navigation items.
 */
class NavigationItemBuilder implements BuilderInterface
{
    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var NavigationItemsProviderInterface */
    private $navigationItemsProvider;

    public function __construct(
        TokenAccessorInterface $tokenAccessor,
        NavigationItemsProviderInterface $navigationItemsProvider
    ) {
        $this->tokenAccessor = $tokenAccessor;
        $this->navigationItemsProvider = $navigationItemsProvider;
    }

    /**
     * Modify menu by adding, removing or editing items.
     *
     * @param \Knp\Menu\ItemInterface $menu
     * @param array                   $options
     * @param string|null             $alias
     */
    public function build(ItemInterface $menu, array $options = [], $alias = null)
    {
        $user = $this->tokenAccessor->getUser();
        $menu->setExtra('type', $alias);
        if (\is_object($user)) {
            $currentOrganization = $this->tokenAccessor->getOrganization();

            $items = $this->navigationItemsProvider->getNavigationItems($user, $currentOrganization, $alias, $options);

            foreach ($items as $item) {
                $menu->addChild(
                    $alias . '_item_' . $item['id'],
                    [
                        'extras' => $item,
                        'uri' => $item['url'],
                        'label' => $item['title']
                    ]
                );
            }
        }
    }
}
