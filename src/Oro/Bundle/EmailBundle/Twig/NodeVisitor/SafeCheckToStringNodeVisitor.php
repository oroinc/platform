<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig\NodeVisitor;

use Oro\Bundle\EmailBundle\Twig\Node\SafeCheckToStringNode;
use Twig\Environment;
use Twig\Node\CheckToStringNode;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Replaces every CheckToStringNode instance (exact class only, not subclasses) with {@see SafeCheckToStringNode}
 * inside the email template sandbox environment.
 *
 * Runs at priority 1, after {@see \Twig\NodeVisitor\SandboxNodeVisitor} (priority 0), which has already wrapped the
 * string-coerced operands into {@see CheckToStringNode} instances.
 */
class SafeCheckToStringNodeVisitor implements NodeVisitorInterface
{
    #[\Override]
    public function enterNode(Node $node, Environment $env): Node
    {
        if (get_class($node) !== CheckToStringNode::class) {
            return $node;
        }

        return new SafeCheckToStringNode($node->getNode('expr'), $node->getAttribute('spread'));
    }

    #[\Override]
    public function leaveNode(Node $node, Environment $env): Node
    {
        return $node;
    }

    #[\Override]
    public function getPriority(): int
    {
        return 1;
    }
}
