<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig\NodeVisitor;

use Oro\Bundle\EmailBundle\Twig\Node\SafeGetAttrNode;
use Oro\Bundle\EntityExtendBundle\Twig\Node\GetAttrNode;
use Twig\Environment;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Replaces every GetAttrNode instance (exact class only, not subclasses) with {@see SafeGetAttrNode}
 * inside the email template sandbox environment.
 *
 * Runs at priority 1, after {@see GetAttrNodeVisitor} (priority 0), which has already replaced the
 * stock Twig {@see GetAttrExpression} nodes with {@see GetAttrNode} instances.
 */
class SafeGetAttrNodeVisitor implements NodeVisitorInterface
{
    #[\Override]
    public function enterNode(Node $node, Environment $env): Node
    {
        if (get_class($node) !== GetAttrNode::class) {
            return $node;
        }

        $nodes = [
            'node' => $node->getNode('node'),
            'attribute' => $node->getNode('attribute'),
        ];

        if ($node->hasNode('arguments')) {
            $nodes['arguments'] = $node->getNode('arguments');
        }

        $attributes = [
            'type' => $node->getAttribute('type'),
            'ignore_strict_check' => $node->getAttribute('ignore_strict_check'),
            'optimizable' => $node->getAttribute('optimizable'),
        ];

        $safeGetAttrNode = new SafeGetAttrNode($nodes, $attributes, $node->getTemplateLine(), $node->getNodeTag());

        if ($node->isDefinedTestEnabled()) {
            $safeGetAttrNode->enableDefinedTest();
        }

        return $safeGetAttrNode;
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
