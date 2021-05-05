<?php

/*
 * This file is a copy of {@see Symfony\Component\ExpressionLanguage\Tests\Node\AbstractNodeTest}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Component\ExpressionLanguage\Tests\Unit\Node;

use Symfony\Component\ExpressionLanguage\Compiler;

abstract class AbstractNodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getEvaluateData
     */
    public function testEvaluate($expected, $node, $variables = [], $functions = [])
    {
        $this->assertSame($expected, $node->evaluate($functions, $variables));
    }

    abstract public function getEvaluateData(): array;

    /**
     * @dataProvider getCompileData
     */
    public function testCompile($expected, $node, $functions = [])
    {
        $compiler = new Compiler($functions);
        $node->compile($compiler);
        $this->assertSame($expected, $compiler->getSource());
    }

    abstract public function getCompileData(): array;

    /**
     * @dataProvider getDumpData
     */
    public function testDump($expected, $node)
    {
        $this->assertSame($expected, $node->dump());
    }

    abstract public function getDumpData(): array;
}
