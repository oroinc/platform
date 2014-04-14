<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig\Fixture;

class EnvironmentNodeVisitor implements \Twig_NodeVisitorInterface
{
    public function enterNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        return $node;
    }

    public function leaveNode(\Twig_NodeInterface $node, \Twig_Environment $env)
    {
        return $node;
    }

    public function getPriority()
    {
        return 0;
    }
}
