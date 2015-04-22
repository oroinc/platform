<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig\TokenParser;

use Oro\Bundle\LayoutBundle\Twig\Node\BlockThemeNode;
use Oro\Bundle\LayoutBundle\Twig\TokenParser\BlockThemeTokenParser;

class BlockThemeTokenParserTest extends \PHPUnit_Framework_TestCase
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
        return [
            [
                '{% block_theme layout "tpl1" %}',
                new BlockThemeNode(
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
                ),
            ],
            [
                '{% block_theme layout "tpl1" "tpl2" %}',
                new BlockThemeNode(
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
                ),
            ],
            [
                '{% block_theme layout with "tpl1" %}',
                new BlockThemeNode(
                    new \Twig_Node_Expression_Name('layout', 1),
                    new \Twig_Node_Expression_Constant('tpl1', 1),
                    1,
                    'block_theme'
                ),
            ],
            [
                '{% block_theme layout with ["tpl1"] %}',
                new BlockThemeNode(
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
                ),
            ],
            [
                '{% block_theme layout with ["tpl1", "tpl2"] %}',
                new BlockThemeNode(
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
                ),
            ],
        ];
    }
}
