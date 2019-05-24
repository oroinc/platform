<?php

namespace Oro\Bundle\LayoutBundle\Twig\Node;

use Oro\Bundle\LayoutBundle\Twig\LayoutExtension;

/**
 * Node for the 'block_theme' tag
 */
class BlockThemeNode extends \Twig_Node
{
    /**
     * @param \Twig_Node $block     A note represents BlockView instance for which additional theme(s) is set
     * @param \Twig_Node $resources A theme name or an array of theme names
     * @param int        $lineno    The line number
     * @param string     $tag       The tag name associated with the Node
     */
    public function __construct(\Twig_Node $block, \Twig_Node $resources, $lineno, $tag = null)
    {
        parent::__construct(['block' => $block, 'resources' => $resources], [], $lineno, $tag);
    }

    /**
     * {@inheritdoc}
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $compiler
            ->addDebugInfo($this)
            ->write(sprintf('$this->env->getExtension("%s")->renderer->setTheme(', LayoutExtension::class))
            ->subcompile($this->getNode('block'))
            ->raw(', ')
            ->subcompile($this->getNode('resources'))
            ->raw(");\n");
    }
}
