<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Twig\NodeVisitor;

use Oro\Bundle\EmailBundle\Twig\Node\SafeGetAttrNode;
use Twig\Environment;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Node;
use Twig\NodeVisitor\NodeVisitorInterface;

/**
 * Replaces every GetAttrExpression instance (exact class only, not subclasses) with {@see SafeGetAttrNode}
 * inside the email template sandbox environment.
 */
class SafeGetAttrNodeVisitor implements NodeVisitorInterface
{
    #[\Override]
    public function enterNode(Node $node, Environment $env): Node
    {
        if (get_class($node) !== GetAttrExpression::class) {
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
            'null_safe' => $node->getAttribute('null_safe'),
            'is_short_circuited' => $node->getAttribute('is_short_circuited'),
            'var_name' => $node->getAttribute('var_name'),
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
