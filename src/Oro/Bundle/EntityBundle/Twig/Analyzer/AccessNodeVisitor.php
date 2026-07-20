<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\Analyzer;

use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\ForNode;
use Twig\Node\Node;
use Twig\Node\SetNode;
use Twig\Template;

/**
 * Recursively traverses a Twig AST and collects all resolved property/method accesses
 * on typed variables. Uses ScopeTracker for variable resolution and TypeResolverInterface
 * for attribute type inference.
 */
class AccessNodeVisitor
{
    public function __construct(
        private readonly TypeResolverInterface $typeResolver,
    ) {
    }

    /**
     * Analyzes the given AST node tree and returns all resolved accesses.
     *
     * @return list<TemplateAccessEntry>
     */
    public function analyze(Node $ast, ScopeTracker $scopeTracker): array
    {
        $accesses = [];
        $this->visitNode($ast, $scopeTracker, $accesses);

        return $accesses;
    }

    /**
     * @param list<TemplateAccessEntry> $accesses
     */
    private function visitNode(Node $node, ScopeTracker $scopeTracker, array &$accesses): void
    {
        if ($node instanceof ForNode) {
            $this->handleForNode($node, $scopeTracker, $accesses);

            return;
        }

        if ($node instanceof SetNode) {
            $this->handleSetNode($node, $scopeTracker, $accesses);

            return;
        }

        if ($node instanceof GetAttrExpression) {
            // Resolve the full chain - this handles all nested GetAttrExpression children internally
            $this->resolveGetAttrChain($node, $scopeTracker, $accesses);

            return;
        }

        // Visit all child nodes
        foreach ($node as $child) {
            if ($child instanceof Node) {
                $this->visitNode($child, $scopeTracker, $accesses);
            }
        }
    }

    /**
     * @param list<TemplateAccessEntry> $accesses
     */
    private function handleForNode(ForNode $node, ScopeTracker $scopeTracker, array &$accesses): void
    {
        // Resolve the sequence expression to determine the element type
        $seqNode = $node->getNode('seq');
        $elementClassName = $this->resolveSequenceElementType($seqNode, $scopeTracker, $accesses);

        // Get the iteration value variable name
        $valueTarget = $node->getNode('value_target');
        $valueName = $valueTarget->getAttribute('name');

        // Push a new scope for the loop body
        $scopeTracker->pushScope();

        if (null !== $elementClassName) {
            $scopeTracker->setVariable($valueName, $elementClassName);
        }

        // Visit the body within the new scope
        $this->visitNode($node->getNode('body'), $scopeTracker, $accesses);

        // Visit the else branch if present
        if ($node->hasNode('else') && null !== $node->getNode('else')) {
            $this->visitNode($node->getNode('else'), $scopeTracker, $accesses);
        }

        $scopeTracker->popScope();
    }

    /**
     * @param list<TemplateAccessEntry> $accesses
     */
    private function handleSetNode(SetNode $node, ScopeTracker $scopeTracker, array &$accesses): void
    {
        $names = $node->getNode('names');
        $values = $node->getNode('values');

        $nameNodes = \iterator_to_array($names);
        $valueNodes = \iterator_to_array($values);

        foreach ($nameNodes as $index => $nameNode) {
            if (!$nameNode instanceof NameExpression) {
                continue;
            }

            $varName = $nameNode->getAttribute('name');
            $valueNode = $valueNodes[$index] ?? null;

            $this->processSetNodeAssignment($varName, $valueNode, $scopeTracker, $accesses);
        }
    }

    /**
     * Processes a single variable assignment from a SetNode.
     *
     * @param list<TemplateAccessEntry> $accesses
     */
    private function processSetNodeAssignment(
        string $varName,
        ?Node $valueNode,
        ScopeTracker $scopeTracker,
        array &$accesses,
    ): void {
        if ($valueNode instanceof GetAttrExpression) {
            $this->assignFromGetAttrExpression($varName, $valueNode, $scopeTracker, $accesses);
        } elseif ($valueNode instanceof NameExpression) {
            $this->assignFromNameExpression($varName, $valueNode, $scopeTracker);
        } elseif ($valueNode instanceof Node) {
            $this->visitNode($valueNode, $scopeTracker, $accesses);
        }
    }

    /**
     * Assigns a variable from a GetAttrExpression.
     *
     * @param list<TemplateAccessEntry> $accesses
     */
    private function assignFromGetAttrExpression(
        string $varName,
        GetAttrExpression $valueNode,
        ScopeTracker $scopeTracker,
        array &$accesses,
    ): void {
        $resolvedType = $this->resolveExpressionType($valueNode, $scopeTracker, $accesses);
        if (null !== $resolvedType) {
            $scopeTracker->setVariable($varName, $resolvedType);
        }
    }

    /**
     * Assigns a variable from a NameExpression.
     */
    private function assignFromNameExpression(
        string $varName,
        NameExpression $valueNode,
        ScopeTracker $scopeTracker,
    ): void {
        $sourceType = $scopeTracker->getVariableType($valueNode->getAttribute('name'));
        if (null !== $sourceType) {
            $scopeTracker->setVariable($varName, $sourceType);
        }
    }

    /**
     * Resolves a GetAttrExpression chain and records all access entries.
     * Returns the resulting class name after the full chain, or null if unresolvable.
     *
     * For array element access (ARRAY_CALL, e.g. entity.groups[0]), resolves the inner
     * collection expression and returns its element type — the same type inference used
     * in ForNode loop iterations — without recording an access entry for the index itself.
     *
     * @param list<TemplateAccessEntry> $accesses
     */
    private function resolveGetAttrChain(
        GetAttrExpression $node,
        ScopeTracker $scopeTracker,
        array &$accesses,
    ): ?string {
        $callType = $node->getAttribute('type');

        // For array element access (e.g. entity.groups[0]), resolve the inner collection
        // and return its element type — matching the type inference used in ForNode iterations.
        if (Template::ARRAY_CALL === $callType) {
            $objectNode = $node->getNode('node');

            return $this->resolveNodeType($objectNode, $scopeTracker, $accesses);
        }

        // Get the attribute name
        $attributeName = $this->getAttributeName($node);
        if (null === $attributeName) {
            return null;
        }

        // Resolve the left-hand side (the object being accessed)
        $objectNode = $node->getNode('node');
        $objectClassName = $this->resolveNodeType($objectNode, $scopeTracker, $accesses);

        if (null === $objectClassName) {
            return null;
        }

        // Resolve this access
        $resolved = $this->typeResolver->resolve($objectClassName, $attributeName, $callType);
        if (null === $resolved) {
            return null;
        }

        // skipAccessEntry is set for virtual variable namespace prefixes (e.g. "url" from "url.view").
        // The full dotted path is handled by EntityVariablesTemplateProcessor before Twig renders the
        // template, so the intermediate step is never actually evaluated at runtime.
        // Suppressing the entry here prevents false positive security-policy violations.
        if ($resolved->skipAccessEntry) {
            return null;
        }

        if ($objectNode->hasAttribute('name')) {
            $variableName = $objectNode->getAttribute('name');
        } else {
            // For chained expressions like entity.groups[0].name the attribute value may be
            // an integer index rather than a string; cast to string for the entry constructor.
            $attrValue = $objectNode->getNode('attribute')->getAttribute('value');
            $variableName = \is_string($attrValue) ? $attrValue : (string)$attrValue;
        }

        // Record the access
        $accesses[] = new TemplateAccessEntry(
            className: $objectClassName,
            variableName: $variableName,
            attributeName: $resolved->attributeName,
            accessType: $resolved->accessType,
            lineNumber: $node->getTemplateLine(),
        );

        return $resolved->entityClass;
    }

    /**
     * Resolves the type that a node expression evaluates to.
     *
     * @param list<TemplateAccessEntry> $accesses
     */
    private function resolveNodeType(Node $node, ScopeTracker $scopeTracker, array &$accesses): ?string
    {
        if ($node instanceof NameExpression) {
            return $scopeTracker->getVariableType($node->getAttribute('name'));
        }

        if ($node instanceof GetAttrExpression) {
            return $this->resolveGetAttrChain($node, $scopeTracker, $accesses);
        }

        return null;
    }

    /**
     * Resolves a GetAttrExpression chain and returns the resulting type.
     * Also records all entries along the way.
     *
     * @param list<TemplateAccessEntry> $accesses
     */
    private function resolveExpressionType(
        AbstractExpression $node,
        ScopeTracker $scopeTracker,
        array &$accesses,
    ): ?string {
        if ($node instanceof GetAttrExpression) {
            return $this->resolveGetAttrChain($node, $scopeTracker, $accesses);
        }

        if ($node instanceof NameExpression) {
            return $scopeTracker->getVariableType($node->getAttribute('name'));
        }

        return null;
    }

    /**
     * Resolves the element type of sequence expression for use in ForNode.
     * Delegates to the standard resolution methods and extracts the element type
     * from the result when it represents a collection.
     *
     * @param list<TemplateAccessEntry> $accesses
     */
    private function resolveSequenceElementType(
        Node $seqNode,
        ScopeTracker $scopeTracker,
        array &$accesses,
    ): ?string {
        if ($seqNode instanceof GetAttrExpression) {
            // Reuse the standard chain resolution (records accesses automatically)
            return $this->resolveGetAttrChain($seqNode, $scopeTracker, $accesses);
        }

        if ($seqNode instanceof NameExpression) {
            return $scopeTracker->getVariableType($seqNode->getAttribute('name'));
        }

        return null;
    }

    private function getAttributeName(GetAttrExpression $node): ?string
    {
        $attrNode = $node->getNode('attribute');
        if ($attrNode instanceof ConstantExpression) {
            $value = $attrNode->getAttribute('value');

            return \is_string($value) ? $value : null;
        }

        return null;
    }
}
