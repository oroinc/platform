<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig\TokenParser;

use Oro\Bundle\LayoutBundle\Twig\Node\BlockThemeNode;
use Oro\Bundle\LayoutBundle\Twig\TokenParser\BlockThemeTokenParser;
use Twig\Source;

class BlockThemeTokenParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getTestsForLayoutTheme
     */
    public function testCompile($source, $expected)
    {
        $env = new \Twig_Environment(
            new \Twig_Loader_String(),
            ['cache' => false, 'autoescape' => false, 'optimizations' => 0]
        );
        $env->addTokenParser(new BlockThemeTokenParser());
        $stream = $env->tokenize($source);
        $parser = new \Twig_Parser($env);

        $this->assertEquals($expected, $parser->parse($stream)->getNode('body')->getNode(0));
    }

    public function getTestsForLayoutTheme()
    {
        $blockThemeNodeTpl1 = new BlockThemeNode(
            new \Twig_Node_Expression_Name('layout', 1),
            new \Twig_Node_Expression_Array(
                [
                    new \Twig_Node_Expression_Constant(0, 1),
                    new \Twig_Node_Expression_Constant('tpl1', 1),
                ],
                1
            ),
            1,
            'block_theme'
        );
        $blockThemeNodeTpl1->setSourceContext(new Source('{% block_theme layout "tpl1" %}', null));

        $blockThemeNodeTpl1Tpl2 = new BlockThemeNode(
            new \Twig_Node_Expression_Name('layout', 1),
            new \Twig_Node_Expression_Array(
                [
                    new \Twig_Node_Expression_Constant(0, 1),
                    new \Twig_Node_Expression_Constant('tpl1', 1),
                    new \Twig_Node_Expression_Constant(1, 1),
                    new \Twig_Node_Expression_Constant('tpl2', 1),
                ],
                1
            ),
            1,
            'block_theme'
        );

        $blockThemeNodeTpl1Tpl2->setSourceContext(new Source('{% block_theme layout "tpl1" "tpl2" %}', null));

        $blockThemeNodeWithTpl1 = new BlockThemeNode(
            new \Twig_Node_Expression_Name('layout', 1),
            new \Twig_Node_Expression_Constant('tpl1', 1),
            1,
            'block_theme'
        );
        $blockThemeNodeWithTpl1->setSourceContext(new Source('{% block_theme layout with "tpl1" %}', null));

        $blockThemeNodeWithTpl1Array = new BlockThemeNode(
            new \Twig_Node_Expression_Name('layout', 1),
            new \Twig_Node_Expression_Array(
                [
                    new \Twig_Node_Expression_Constant(0, 1),
                    new \Twig_Node_Expression_Constant('tpl1', 1),
                ],
                1
            ),
            1,
            'block_theme'
        );
        $blockThemeNodeWithTpl1Array->setSourceContext(new Source('{% block_theme layout with ["tpl1"] %}', null));

        $blockThemeNodeWithTpl1Tpl2Array = new BlockThemeNode(
            new \Twig_Node_Expression_Name('layout', 1),
            new \Twig_Node_Expression_Array(
                [
                    new \Twig_Node_Expression_Constant(0, 1),
                    new \Twig_Node_Expression_Constant('tpl1', 1),
                    new \Twig_Node_Expression_Constant(1, 1),
                    new \Twig_Node_Expression_Constant('tpl2', 1),
                ],
                1
            ),
            1,
            'block_theme'
        );
        $blockThemeNodeWithTpl1Tpl2Array->setSourceContext(
            new Source('{% block_theme layout with ["tpl1", "tpl2"] %}', null)
        );

        return [
            [
                '{% block_theme layout "tpl1" %}',
                $blockThemeNodeTpl1,
            ],
            [
                '{% block_theme layout "tpl1" "tpl2" %}',
                $blockThemeNodeTpl1Tpl2,
            ],
            [
                '{% block_theme layout with "tpl1" %}',
                $blockThemeNodeWithTpl1
            ],
            [
                '{% block_theme layout with ["tpl1"] %}',
                $blockThemeNodeWithTpl1Array
            ],
            [
                '{% block_theme layout with ["tpl1", "tpl2"] %}',
                $blockThemeNodeWithTpl1Tpl2Array
            ],
        ];
    }
}
