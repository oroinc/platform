<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture;

use Twig\Environment;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

class EnvironmentNodeVisitor implements NodeVisitorInterface
{
    #[\Override]
    public function enterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    #[\Override]
    public function leaveNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    #[\Override]
    public function getPriority()
    {
        return 0;
    }
}
