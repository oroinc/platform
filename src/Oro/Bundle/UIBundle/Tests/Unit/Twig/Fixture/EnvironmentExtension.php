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
    #[\Override]
    public function getTokenParsers()
    {
        return [
            new EnvironmentTokenParser(),
        ];
    }

    #[\Override]
    public function getNodeVisitors()
    {
        return [
            new EnvironmentNodeVisitor(),
        ];
    }

    #[\Override]
    public function getFilters()
    {
        return [
            new TwigFilter('foo_filter', 'foo_filter'),
        ];
    }

    #[\Override]
    public function getTests()
    {
        return [
            new TwigTest('foo_test', 'foo_test'),
        ];
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('foo_function', 'foo_function'),
        ];
    }

    #[\Override]
    public function getOperators()
    {
        return [
            ['foo_unary' => []],
            ['foo_binary' => []],
        ];
    }

    #[\Override]
    public function getGlobals(): array
    {
        return [
            'foo_global' => 'foo_global',
        ];
    }
}
