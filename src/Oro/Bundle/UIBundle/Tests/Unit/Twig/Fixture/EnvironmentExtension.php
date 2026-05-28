<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Node\Expression\Binary\AndBinary;
use Twig\Node\Expression\Unary\NegUnary;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * A test stub
 */
class EnvironmentExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @inheritdoc
     */
    public function getTokenParsers()
    {
        return [
            new EnvironmentTokenParser(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getNodeVisitors()
    {
        return [
            new EnvironmentNodeVisitor(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getFilters()
    {
        return [
            new TwigFilter('foo_filter', 'foo_filter'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getTests()
    {
        return [
            new TwigTest('foo_test', 'foo_test'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('foo_function', 'foo_function'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getOperators()
    {
        return [
            ['foo_unary' => ['precedence' => 500, 'class' => NegUnary::class]],
            ['foo_binary' => ['precedence' => 10, 'class' => AndBinary::class, 'associativity' => 1]],
        ];
    }

    /**
     * @inheritdoc
     */
    public function getGlobals(): array
    {
        return [
            'foo_global' => 'foo_global',
        ];
    }
}
