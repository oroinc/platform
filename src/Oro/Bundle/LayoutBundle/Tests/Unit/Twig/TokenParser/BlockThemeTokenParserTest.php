<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig\TokenParser;

use Oro\Bundle\LayoutBundle\Twig\Node\LayoutThemeNode;
use Oro\Bundle\LayoutBundle\Twig\TokenParser\LayoutThemeTokenParser;

class LayoutThemeTokenParserTest extends \PHPUnit_Framework_TestCase
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
        $env->addTokenParser(new LayoutThemeTokenParser());
        $stream = $env->tokenize($source);
        $parser = new \Twig_Parser($env);

        $this->assertEquals($expected, $parser->parse($stream)->getNode('body')->getNode(0));
    }

    public function getTestsForLayoutTheme()
    {
        return [
            [
                '{% layout_theme layout "tpl1" %}',
                new LayoutThemeNode(
                    new \Twig_Node_Expression_Name('layout', 1),
                    new \Twig_Node_Expression_Array(
                        [
                            new \Twig_Node_Expression_Constant(0, 1),
                            new \Twig_Node_Expression_Constant('tpl1', 1),
                        ],
                        1
                    ),
                    1,
                    'layout_theme'
                ),
            ],
            [
                '{% layout_theme layout "tpl1" "tpl2" %}',
                new LayoutThemeNode(
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
                    'layout_theme'
                ),
            ],
            [
                '{% layout_theme layout with "tpl1" %}',
                new LayoutThemeNode(
                    new \Twig_Node_Expression_Name('layout', 1),
                    new \Twig_Node_Expression_Constant('tpl1', 1),
                    1,
                    'layout_theme'
                ),
            ],
            [
                '{% layout_theme layout with ["tpl1"] %}',
                new LayoutThemeNode(
                    new \Twig_Node_Expression_Name('layout', 1),
                    new \Twig_Node_Expression_Array(
                        [
                            new \Twig_Node_Expression_Constant(0, 1),
                            new \Twig_Node_Expression_Constant('tpl1', 1),
                        ],
                        1
                    ),
                    1,
                    'layout_theme'
                ),
            ],
            [
                '{% layout_theme layout with ["tpl1", "tpl2"] %}',
                new LayoutThemeNode(
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
                    'layout_theme'
                ),
            ],
        ];
    }
}
