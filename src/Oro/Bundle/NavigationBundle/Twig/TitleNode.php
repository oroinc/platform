<?php

namespace Oro\Bundle\NavigationBundle\Twig;

use Twig\Compiler;
use Twig\Error\SyntaxError;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Node;

/**
 * Compile title node to template
 */
class TitleNode extends Node
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
     * @throws SyntaxError
     */
    public function compile(Compiler $compiler)
    {
        $node = $this->getNode('expr');

        $arguments = null;

        $nodes = $node->getIterator();

        // take first argument array node
        foreach ($nodes as $childNode) {
            if ($childNode instanceof ArrayExpression) {
                $arguments = $childNode;

                break;
            }
        }

        if ($arguments === null) {
            throw new SyntaxError('Function oro_title_set expected argument: array');
        }

        $compiler
            ->raw("\n")
            ->write(sprintf('$this->env->getExtension("%s")->set(', TitleExtension::class))
            ->subcompile($arguments)
            ->raw(");\n");
    }
}
