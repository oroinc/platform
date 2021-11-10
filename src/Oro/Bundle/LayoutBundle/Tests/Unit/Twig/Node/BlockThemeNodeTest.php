<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig\Node;

use Oro\Bundle\LayoutBundle\Twig\LayoutExtension;
use Oro\Bundle\LayoutBundle\Twig\Node\BlockThemeNode;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Twig\Compiler;
use Twig\Environment;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;

class BlockThemeNodeTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private const SET_THEME_CALL = '$this->env->getExtension("' . LayoutExtension::class . '")->renderer->setTheme';

    public function testCompile()
    {
        $block = new Node(
            [
                new NameExpression('layout', 0)
            ]
        );
        $resources = new Node(
            [
                new ConstantExpression('@Some/Layout/blocks.html.twig', 0)
            ]
        );

        $node = new BlockThemeNode($block, $resources, 0);

        $compiler = new Compiler(new Environment($this->getLoader()));

        $this->assertEquals(
            sprintf(
                self::SET_THEME_CALL . '(%s, "@Some/Layout/blocks.html.twig");',
                $this->getVariableGetter('layout')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    private function getVariableGetter(string $name): string
    {
        if (PHP_VERSION_ID >= 70000) {
            return sprintf('($context["%s"] ?? null)', $name);
        }
        if (PHP_VERSION_ID >= 50400) {
            return sprintf('(isset($context["%s"]) ? $context["%s"] : null)', $name, $name);
        }

        return sprintf('$this->getContext($context, "%s")', $name);
    }
}
