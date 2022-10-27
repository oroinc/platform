<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig\TokenParser;

use Oro\Bundle\LayoutBundle\Twig\Node\BlockThemeNode;
use Oro\Bundle\LayoutBundle\Twig\TokenParser\BlockThemeTokenParser;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Twig\Environment;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Parser;
use Twig\Source;

class BlockThemeTokenParserTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /**
     * @dataProvider getTestsForLayoutTheme
     */
    public function testCompile(Source $source, BlockThemeNode $expected)
    {
        $env = new Environment(
            $this->getLoader(),
            ['cache' => false, 'autoescape' => false, 'optimizations' => 0]
        );
        $env->addTokenParser(new BlockThemeTokenParser());
        $stream = $env->tokenize($source);
        $parser = new Parser($env);

        $this->assertEquals($expected, $parser->parse($stream)->getNode('body')->getNode(0));
    }

    public function getTestsForLayoutTheme()
    {
        $sourceBlockThemeNodeTpl1 = new Source('{% block_theme layout "tpl1" %}', 'index');
        $blockThemeNodeTpl1 = new BlockThemeNode(
            new NameExpression('layout', 1),
            new ArrayExpression(
                [
                    new ConstantExpression(0, 1),
                    new ConstantExpression('tpl1', 1),
                ],
                1
            ),
            1,
            'block_theme'
        );
        $blockThemeNodeTpl1->setSourceContext($sourceBlockThemeNodeTpl1);

        $sourceBlockThemeNodeTpl1Tpl2 = new Source('{% block_theme layout "tpl1" "tpl2" %}', 'index');
        $blockThemeNodeTpl1Tpl2 = new BlockThemeNode(
            new NameExpression('layout', 1),
            new ArrayExpression(
                [
                    new ConstantExpression(0, 1),
                    new ConstantExpression('tpl1', 1),
                    new ConstantExpression(1, 1),
                    new ConstantExpression('tpl2', 1),
                ],
                1
            ),
            1,
            'block_theme'
        );

        $blockThemeNodeTpl1Tpl2->setSourceContext($sourceBlockThemeNodeTpl1Tpl2);

        $sourceBlockThemeNodeWithTpl1 = new Source('{% block_theme layout with "tpl1" %}', 'index');
        $blockThemeNodeWithTpl1 = new BlockThemeNode(
            new NameExpression('layout', 1),
            new ConstantExpression('tpl1', 1),
            1,
            'block_theme'
        );
        $blockThemeNodeWithTpl1->setSourceContext($sourceBlockThemeNodeWithTpl1);

        $sourceBlockThemeNodeWithTpl1Array = new Source('{% block_theme layout with ["tpl1"] %}', 'index');
        $blockThemeNodeWithTpl1Array = new BlockThemeNode(
            new NameExpression('layout', 1),
            new ArrayExpression(
                [
                    new ConstantExpression(0, 1),
                    new ConstantExpression('tpl1', 1),
                ],
                1
            ),
            1,
            'block_theme'
        );
        $blockThemeNodeWithTpl1Array->setSourceContext($sourceBlockThemeNodeWithTpl1Array);

        $sourceBlockThemeNodeWithTpl1Tpl2Array = new Source('{% block_theme layout with ["tpl1", "tpl2"] %}', 'index');
        $blockThemeNodeWithTpl1Tpl2Array = new BlockThemeNode(
            new NameExpression('layout', 1),
            new ArrayExpression(
                [
                    new ConstantExpression(0, 1),
                    new ConstantExpression('tpl1', 1),
                    new ConstantExpression(1, 1),
                    new ConstantExpression('tpl2', 1),
                ],
                1
            ),
            1,
            'block_theme'
        );
        $blockThemeNodeWithTpl1Tpl2Array->setSourceContext($sourceBlockThemeNodeWithTpl1Tpl2Array);

        return [
            [
                $sourceBlockThemeNodeTpl1,
                $blockThemeNodeTpl1,
            ],
            [
                $sourceBlockThemeNodeTpl1Tpl2,
                $blockThemeNodeTpl1Tpl2,
            ],
            [
                $sourceBlockThemeNodeWithTpl1,
                $blockThemeNodeWithTpl1
            ],
            [
                $sourceBlockThemeNodeWithTpl1Array,
                $blockThemeNodeWithTpl1Array
            ],
            [
                $sourceBlockThemeNodeWithTpl1Tpl2Array,
                $blockThemeNodeWithTpl1Tpl2Array
            ],
        ];
    }
}
