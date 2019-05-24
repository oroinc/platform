<?php

namespace Oro\Bundle\NavigationBundle\Twig;

use Twig\Compiler;
use Twig\Node\Node;

/**
 * Used for compiling title nodes.
 */
class TitleNode extends \Twig_Node
{
    /**
     * @param Node|null $expr
     * @param int $lineno
     * @param null $tag
     */
    public function __construct(Node $expr = null, $lineno = 0, $tag = null)
    {
        parent::__construct(['expr' => $expr], [], $lineno, $tag);
    }

    /**
     * Compile title node to template
     *
     * @param  Compiler $compiler
     * @throws \Twig_Error_Syntax
     */
    public function compile(Compiler $compiler)
    {
        $node = $this->getNode('expr');

        $arguments = null;

        $nodes = $node->getIterator();

        // take first argument array node
        foreach ($nodes as $childNode) {
            if ($childNode instanceof \Twig_Node_Expression_Array) {
                $arguments = $childNode;

                break;
            }
        }

        if ($arguments === null) {
            throw new \Twig_Error_Syntax('Function oro_title_set expected argument: array');
        }

        $compiler
            ->raw("\n")
            ->write(sprintf('$this->env->getExtension("%s")->set(', TitleExtension::class))
            ->subcompile($arguments)
            ->raw(");\n");
    }
}
