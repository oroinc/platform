<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture;

use Twig\Environment;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

class EnvironmentNodeVisitor implements NodeVisitorInterface
{
    public function enterNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    public function leaveNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    public function getPriority()
    {
        return 0;
    }
}
