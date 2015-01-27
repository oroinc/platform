<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig\Node;

use Oro\Bundle\LayoutBundle\Twig\Node\SearchAndRenderBlockNode;

class SearchAndRenderBlockNodeTest extends \PHPUnit_Framework_TestCase
{
    const RENDER_CALL = '$this->env->getExtension(\'layout\')->renderer->searchAndRenderBlock';

    /**
     * layout_widget(item)
     */
    public function testCompileWidget()
    {
        $arguments = new \Twig_Node(
            [
                new \Twig_Node_Expression_Name('item', 0),
            ]
        );

        $node = new SearchAndRenderBlockNode('layout_widget', $arguments, 0);

        $compiler = new \Twig_Compiler(new \Twig_Environment());

        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'widget\')',
                $this->getVariableGetter('item')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * layout_widget(item, {'foo' => 'bar'})
     */
    public function testCompileWidgetWithVariables()
    {
        $arguments = new \Twig_Node(
            [
                new \Twig_Node_Expression_Name('item', 0),
                new \Twig_Node_Expression_Array(
                    [
                        new \Twig_Node_Expression_Constant('foo', 0),
                        new \Twig_Node_Expression_Constant('bar', 0),
                    ],
                    0
                ),
            ]
        );

        $node = new SearchAndRenderBlockNode('layout_widget', $arguments, 0);

        $compiler = new \Twig_Compiler(new \Twig_Environment());

        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'widget\', array("foo" => "bar"))',
                $this->getVariableGetter('item')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * layout_label(item, 'my label')
     */
    public function testCompileLabelWithLabel()
    {
        $arguments = new \Twig_Node(
            [
                new \Twig_Node_Expression_Name('item', 0),
                new \Twig_Node_Expression_Constant('my label', 0),
            ]
        );

        $node = new SearchAndRenderBlockNode('layout_label', $arguments, 0);

        $compiler = new \Twig_Compiler(new \Twig_Environment());

        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\', array("label" => "my label"))',
                $this->getVariableGetter('item')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * layout_label(item, null)
     */
    public function testCompileLabelWithNullLabel()
    {
        $arguments = new \Twig_Node(
            [
                new \Twig_Node_Expression_Name('item', 0),
                new \Twig_Node_Expression_Constant(null, 0),
            ]
        );

        $node = new SearchAndRenderBlockNode('layout_label', $arguments, 0);

        $compiler = new \Twig_Compiler(new \Twig_Environment());

        // "label" => null must not be included in the output!
        // Otherwise the default label is overwritten with null.
        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\')',
                $this->getVariableGetter('item')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * layout_label(item, '')
     */
    public function testCompileLabelWithEmptyStringLabel()
    {
        $arguments = new \Twig_Node(
            [
                new \Twig_Node_Expression_Name('item', 0),
                new \Twig_Node_Expression_Constant('', 0),
            ]
        );

        $node = new SearchAndRenderBlockNode('layout_label', $arguments, 0);

        $compiler = new \Twig_Compiler(new \Twig_Environment());

        // "label" => null must not be included in the output!
        // Otherwise the default label is overwritten with null.
        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\')',
                $this->getVariableGetter('item')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * layout_label(item)
     */
    public function testCompileLabelWithDefaultLabel()
    {
        $arguments = new \Twig_Node(
            [
                new \Twig_Node_Expression_Name('item', 0),
            ]
        );

        $node = new SearchAndRenderBlockNode('layout_label', $arguments, 0);

        $compiler = new \Twig_Compiler(new \Twig_Environment());

        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\')',
                $this->getVariableGetter('item')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * layout_label(item, null, {'foo' => 'bar'})
     */
    public function testCompileLabelWithAttributes()
    {
        $arguments = new \Twig_Node(
            [
                new \Twig_Node_Expression_Name('item', 0),
                new \Twig_Node_Expression_Constant(null, 0),
                new \Twig_Node_Expression_Array(
                    [
                        new \Twig_Node_Expression_Constant('foo', 0),
                        new \Twig_Node_Expression_Constant('bar', 0),
                    ],
                    0
                ),
            ]
        );

        $node = new SearchAndRenderBlockNode('layout_label', $arguments, 0);

        $compiler = new \Twig_Compiler(new \Twig_Environment());

        // "label" => null must not be included in the output!
        // Otherwise the default label is overwritten with null.
        // https://github.com/symfony/symfony/issues/5029
        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\', array("foo" => "bar"))',
                $this->getVariableGetter('item')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * layout_label(item, 'value in argument', {'foo' => 'bar', 'label' => value in attributes})
     */
    public function testCompileLabelWithLabelAndAttributes()
    {
        $arguments = new \Twig_Node(
            [
                new \Twig_Node_Expression_Name('item', 0),
                new \Twig_Node_Expression_Constant('value in argument', 0),
                new \Twig_Node_Expression_Array(
                    [
                        new \Twig_Node_Expression_Constant('foo', 0),
                        new \Twig_Node_Expression_Constant('bar', 0),
                        new \Twig_Node_Expression_Constant('label', 0),
                        new \Twig_Node_Expression_Constant('value in attributes', 0),
                    ],
                    0
                ),
            ]
        );

        $node = new SearchAndRenderBlockNode('layout_label', $arguments, 0);

        $compiler = new \Twig_Compiler(new \Twig_Environment());

        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\', array("foo" => "bar", "label" => "value in argument"))',
                $this->getVariableGetter('item')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * layout_label(item, true ? null : null)
     */
    public function testCompileLabelWithLabelThatEvaluatesToNull()
    {
        $arguments = new \Twig_Node(
            [
                new \Twig_Node_Expression_Name('item', 0),
                new \Twig_Node_Expression_Conditional(
                    new \Twig_Node_Expression_Constant(true, 0), // if
                    new \Twig_Node_Expression_Constant(null, 0), // then
                    new \Twig_Node_Expression_Constant(null, 0), // else
                    0
                ),
            ]
        );

        $node = new SearchAndRenderBlockNode('layout_label', $arguments, 0);

        $compiler = new \Twig_Compiler(new \Twig_Environment());

        // "label" => null must not be included in the output!
        // Otherwise the default label is overwritten with null.
        // https://github.com/symfony/symfony/issues/5029
        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\', '
                . '(twig_test_empty($_label_ = ((true) ? (null) : (null))) ? array() : array("label" => $_label_)))',
                $this->getVariableGetter('item')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * layout_label(item, true ? null : null, {'foo' => 'bar', 'label' => value in attributes})
     */
    public function testCompileLabelWithLabelThatEvaluatesToNullAndAttributes()
    {
        $arguments = new \Twig_Node(
            [
                new \Twig_Node_Expression_Name('item', 0),
                new \Twig_Node_Expression_Conditional(
                    new \Twig_Node_Expression_Constant(true, 0), // if
                    new \Twig_Node_Expression_Constant(null, 0), // then
                    new \Twig_Node_Expression_Constant(null, 0), // else
                    0
                ),
                new \Twig_Node_Expression_Array(
                    [
                        new \Twig_Node_Expression_Constant('foo', 0),
                        new \Twig_Node_Expression_Constant('bar', 0),
                        new \Twig_Node_Expression_Constant('label', 0),
                        new \Twig_Node_Expression_Constant('value in attributes', 0),
                    ],
                    0
                ),
            ]
        );

        $node = new SearchAndRenderBlockNode('layout_label', $arguments, 0);

        $compiler = new \Twig_Compiler(new \Twig_Environment());

        // "label" => null must not be included in the output!
        // Otherwise the default label is overwritten with null.
        // https://github.com/symfony/symfony/issues/5029
        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\', '
                . 'array("foo" => "bar", "label" => "value in attributes") '
                . '+ (twig_test_empty($_label_ = ((true) ? (null) : (null))) ? array() : array("label" => $_label_)))',
                $this->getVariableGetter('item')
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
