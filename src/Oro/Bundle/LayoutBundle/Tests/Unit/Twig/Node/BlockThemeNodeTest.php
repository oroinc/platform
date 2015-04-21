<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig\Node;

use Oro\Bundle\LayoutBundle\Twig\Node\BlockThemeNode;

class BlockThemeNodeTest extends \PHPUnit_Framework_TestCase
{
    const SET_THEME_CALL = '$this->env->getExtension(\'layout\')->renderer->setTheme';

    public function testCompile()
    {
        $block = new \Twig_Node(
            [
                new \Twig_Node_Expression_Name('layout', 0)
            ]
        );
        $resources = new \Twig_Node(
            [
                new \Twig_Node_Expression_Constant('SomeBundle:Layout:blocks.html.twig', 0)
            ]
        );

        $node = new BlockThemeNode($block, $resources, 0);

        $compiler = new \Twig_Compiler(new \Twig_Environment());

        $this->assertEquals(
            sprintf(
                self::SET_THEME_CALL . '(%s, "SomeBundle:Layout:blocks.html.twig");',
                $this->getVariableGetter('layout')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    protected function getVariableGetter($name)
    {
        if (PHP_VERSION_ID >= 50400) {
            return sprintf('(isset($context["%s"]) ? $context["%s"] : null)', $name, $name);
        }

        return sprintf('$this->getContext($context, "%s")', $name);
    }
}
