<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Node\Expression\Binary\AddBinary;
use Twig\Node\Expression\Unary\NotUnary;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * A test stub
 */
class EnvironmentExtension extends AbstractExtension implements GlobalsInterface
{
    #[\Override]
    public function getTokenParsers(): array
    {
        return [
            new EnvironmentTokenParser()
        ];
    }

    #[\Override]
    public function getNodeVisitors(): array
    {
        return [
            new EnvironmentNodeVisitor()
        ];
    }

    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('foo_filter', 'foo_filter')
        ];
    }

    #[\Override]
    public function getTests(): array
    {
        return [
            new TwigTest('foo_test', 'foo_test')
        ];
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('foo_function', 'foo_function')
        ];
    }

    #[\Override]
    public function getOperators(): array
    {
        return [
            [
                'foo_unary' => [
                    'class' => NotUnary::class,
                    'precedence' => 0,
                    'precedence_change' => null,
                    'aliases' => []
                ]
            ],
            [
                'foo_binary' => [
                    'class' => AddBinary::class,
                    'precedence' => 0,
                    'associativity' => 1,
                    'precedence_change' => null,
                    'aliases' => []
                ]
            ]
        ];
    }

    #[\Override]
    public function getGlobals(): array
    {
        return [
            'foo_global' => 'foo_global'
        ];
    }
}
