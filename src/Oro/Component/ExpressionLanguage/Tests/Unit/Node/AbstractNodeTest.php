<?php

/*
 * This file is a copy of {@see Symfony\Component\ExpressionLanguage\Tests\Node\AbstractNodeTest}
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 */

namespace Oro\Component\ExpressionLanguage\Tests\Unit\Node;

use Symfony\Component\ExpressionLanguage\Compiler;
use Symfony\Component\ExpressionLanguage\Node\Node;

abstract class AbstractNodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getEvaluateData
     */
    public function testEvaluate($expected, Node $node, $variables = [], $functions = []): void
    {
        self::assertSame($expected, $node->evaluate($functions, $variables));
    }

    abstract public function getEvaluateData(): array;

    /**
     * @dataProvider getCompileData
     */
    public function testCompile($expected, Node $node, $functions = []): void
    {
        $compiler = new Compiler($functions);
        $node->compile($compiler);
        self::assertSame($expected, $compiler->getSource());
    }

    abstract public function getCompileData(): array;

    /**
     * @dataProvider getDumpData
     */
    public function testDump($expected, Node $node): void
    {
        self::assertSame($expected, $node->dump());
    }

    abstract public function getDumpData(): array;
}
