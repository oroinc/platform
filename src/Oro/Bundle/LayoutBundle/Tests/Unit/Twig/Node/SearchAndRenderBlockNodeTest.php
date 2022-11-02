<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig\Node;

use Oro\Bundle\LayoutBundle\Twig\Node\SearchAndRenderBlockNode;
use Oro\Bundle\LayoutBundle\Twig\TwigRenderer;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Twig\Compiler;
use Twig\Environment;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConditionalExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchAndRenderBlockNodeTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    private const RENDER_CALL = '$this->env->getRuntime("' . TwigRenderer::class . '")->searchAndRenderBlock';

    /**
     * block_widget(block)
     */
    public function testCompileWidget(): void
    {
        $arguments = new Node(
            [
                new NameExpression('block', 0),
            ]
        );

        $node = new SearchAndRenderBlockNode('block_widget', $arguments, 0);

        $compiler = new Compiler(new Environment($this->getLoader()));

        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'widget\')',
                $this->getVariableGetter('block')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * block_widget(block, {'foo' => 'bar'})
     */
    public function testCompileWidgetWithVariables()
    {
        $arguments = new Node(
            [
                new NameExpression('block', 0),
                new ArrayExpression(
                    [
                        new ConstantExpression('foo', 0),
                        new ConstantExpression('bar', 0),
                    ],
                    0
                ),
            ]
        );

        $node = new SearchAndRenderBlockNode('block_widget', $arguments, 0);

        $compiler = new Compiler(new Environment($this->getLoader()));

        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'widget\', ["foo" => "bar"])',
                $this->getVariableGetter('block')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * block_label(block, 'my label')
     */
    public function testCompileLabelWithLabel()
    {
        $arguments = new Node(
            [
                new NameExpression('block', 0),
                new ConstantExpression('my label', 0),
            ]
        );

        $node = new SearchAndRenderBlockNode('block_label', $arguments, 0);

        $compiler = new Compiler(new Environment($this->getLoader()));

        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\', ["label" => "my label"])',
                $this->getVariableGetter('block')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * block_label(block, null)
     */
    public function testCompileLabelWithNullLabel()
    {
        $arguments = new Node(
            [
                new NameExpression('block', 0),
                new ConstantExpression(null, 0),
            ]
        );

        $node = new SearchAndRenderBlockNode('block_label', $arguments, 0);

        $compiler = new Compiler(new Environment($this->getLoader()));

        // "label" => null must not be included in the output!
        // Otherwise the default label is overwritten with null.
        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\')',
                $this->getVariableGetter('block')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * block_label(block, '')
     */
    public function testCompileLabelWithEmptyStringLabel()
    {
        $arguments = new Node(
            [
                new NameExpression('block', 0),
                new ConstantExpression('', 0),
            ]
        );

        $node = new SearchAndRenderBlockNode('block_label', $arguments, 0);

        $compiler = new Compiler(new Environment($this->getLoader()));

        // "label" => null must not be included in the output!
        // Otherwise the default label is overwritten with null.
        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\')',
                $this->getVariableGetter('block')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * block_label(block)
     */
    public function testCompileLabelWithDefaultLabel()
    {
        $arguments = new Node(
            [
                new NameExpression('block', 0),
            ]
        );

        $node = new SearchAndRenderBlockNode('block_label', $arguments, 0);

        $compiler = new Compiler(new Environment($this->getLoader()));

        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\')',
                $this->getVariableGetter('block')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * block_label(block, null, {'foo' => 'bar'})
     */
    public function testCompileLabelWithAttributes()
    {
        $arguments = new Node(
            [
                new NameExpression('block', 0),
                new ConstantExpression(null, 0),
                new ArrayExpression(
                    [
                        new ConstantExpression('foo', 0),
                        new ConstantExpression('bar', 0),
                    ],
                    0
                ),
            ]
        );

        $node = new SearchAndRenderBlockNode('block_label', $arguments, 0);

        $compiler = new Compiler(new Environment($this->getLoader()));

        // "label" => null must not be included in the output!
        // Otherwise the default label is overwritten with null.
        // https://github.com/symfony/symfony/issues/5029
        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\', ["foo" => "bar"])',
                $this->getVariableGetter('block')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * block_label(block, 'value in argument', {'foo' => 'bar', 'label' => value in attributes})
     */
    public function testCompileLabelWithLabelAndAttributes()
    {
        $arguments = new Node(
            [
                new NameExpression('block', 0),
                new ConstantExpression('value in argument', 0),
                new ArrayExpression(
                    [
                        new ConstantExpression('foo', 0),
                        new ConstantExpression('bar', 0),
                        new ConstantExpression('label', 0),
                        new ConstantExpression('value in attributes', 0),
                    ],
                    0
                ),
            ]
        );

        $node = new SearchAndRenderBlockNode('block_label', $arguments, 0);

        $compiler = new Compiler(new Environment($this->getLoader()));

        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\', ["foo" => "bar", "label" => "value in argument"])',
                $this->getVariableGetter('block')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * block_label(block, true ? null : null)
     */
    public function testCompileLabelWithLabelThatEvaluatesToNull()
    {
        $arguments = new Node(
            [
                new NameExpression('block', 0),
                new ConditionalExpression(
                    new ConstantExpression(true, 0), // if
                    new ConstantExpression(null, 0), // then
                    new ConstantExpression(null, 0), // else
                    0
                ),
            ]
        );

        $node = new SearchAndRenderBlockNode('block_label', $arguments, 0);

        $compiler = new Compiler(new Environment($this->getLoader()));

        // "label" => null must not be included in the output!
        // Otherwise the default label is overwritten with null.
        // https://github.com/symfony/symfony/issues/5029
        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\', '
                . '(twig_test_empty($_label_ = ((true) ? (null) : (null))) ? array() : array("label" => $_label_)))',
                $this->getVariableGetter('block')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    /**
     * block_label(block, true ? null : null, {'foo' => 'bar', 'label' => value in attributes})
     */
    public function testCompileLabelWithLabelThatEvaluatesToNullAndAttributes()
    {
        $arguments = new Node(
            [
                new NameExpression('block', 0),
                new ConditionalExpression(
                    new ConstantExpression(true, 0), // if
                    new ConstantExpression(null, 0), // then
                    new ConstantExpression(null, 0), // else
                    0
                ),
                new ArrayExpression(
                    [
                        new ConstantExpression('foo', 0),
                        new ConstantExpression('bar', 0),
                        new ConstantExpression('label', 0),
                        new ConstantExpression('value in attributes', 0),
                    ],
                    0
                ),
            ]
        );

        $node = new SearchAndRenderBlockNode('block_label', $arguments, 0);

        $compiler = new Compiler(new Environment($this->getLoader()));

        // "label" => null must not be included in the output!
        // Otherwise the default label is overwritten with null.
        // https://github.com/symfony/symfony/issues/5029
        $this->assertEquals(
            sprintf(
                self::RENDER_CALL . '(%s, \'label\', '
                . '["foo" => "bar", "label" => "value in attributes"] '
                . '+ (twig_test_empty($_label_ = ((true) ? (null) : (null))) ? array() : array("label" => $_label_)))',
                $this->getVariableGetter('block')
            ),
            trim($compiler->compile($node)->getSource())
        );
    }

    public function testCompileParentBlockWidget()
    {
        $arguments = new Node(
            [
                new NameExpression('block', 0),
            ]
        );

        $node = new SearchAndRenderBlockNode('parent_block_widget', $arguments, 0);

        $compiler = new Compiler(new Environment($this->getLoader()));

        $this->assertEquals(
            self::RENDER_CALL . '($context[\'block\'], \'widget\', $context, true)',
            trim($compiler->compile($node)->getSource())
        );
    }

    private function getVariableGetter(string $name): string
    {
        return sprintf('($context["%s"] ?? null)', $name);
    }
}
