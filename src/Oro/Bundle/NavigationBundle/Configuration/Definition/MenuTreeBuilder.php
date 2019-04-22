<?php

namespace Oro\Bundle\NavigationBundle\Configuration\Definition;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * The builder for menu node.
 */
class MenuTreeBuilder extends NodeBuilder
{
    public function __construct()
    {
        parent::__construct();

        $this->nodeMapping['menu'] = __NAMESPACE__ . '\\MenuNodeDefinition';
    }

    /**
     * Creates a child menu node.
     *
     * @param string $name The name of the node
     *
     * @return MenuNodeDefinition The menu node
     */
    public function menuNode($name)
    {
        return $this->node($name, 'menu');
    }
}
