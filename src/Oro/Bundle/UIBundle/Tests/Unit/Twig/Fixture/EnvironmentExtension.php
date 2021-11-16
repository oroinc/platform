<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * A test stub
 */
class EnvironmentExtension extends AbstractExtension implements GlobalsInterface
{
    public function getTokenParsers()
    {
        return [
            new EnvironmentTokenParser(),
        ];
    }

    public function getNodeVisitors()
    {
        return [
            new EnvironmentNodeVisitor(),
        ];
    }

    public function getFilters()
    {
        return [
            new TwigFilter('foo_filter', 'foo_filter'),
        ];
    }

    public function getTests()
    {
        return [
            new TwigTest('foo_test', 'foo_test'),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('foo_function', 'foo_function'),
        ];
    }

    public function getOperators()
    {
        return [
            ['foo_unary' => []],
            ['foo_binary' => []],
        ];
    }

    public function getGlobals(): array
    {
        return [
            'foo_global' => 'foo_global',
        ];
    }
}
