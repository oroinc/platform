<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Twig\Analyzer;

/**
 * Maintains a stack of symbol tables (variable name to FQCN mappings) to support nested scopes
 * introduced by for loops and set assignments in Twig templates.
 */
final class ScopeTracker
{
    /** @var list<array<string, string>> */
    private array $scopeStack;

    /**
     * @param array<string, string> $initialVariables Map of variable names to FQCNs
     */
    public function __construct(array $initialVariables)
    {
        $this->scopeStack = [$initialVariables];
    }

    /**
     * Pushes a new empty scope onto the stack.
     * Called when entering a ForNode.
     */
    public function pushScope(): void
    {
        $this->scopeStack[] = [];
    }

    /**
     * Pops the top scope from the stack.
     * Called when leaving a ForNode.
     *
     * @throws \LogicException If the scope stack has only the root scope remaining.
     */
    public function popScope(): void
    {
        if (\count($this->scopeStack) <= 1) {
            throw new \LogicException('Cannot pop the root scope from ScopeTracker.');
        }

        array_pop($this->scopeStack);
    }

    /**
     * Sets a variable in the current (topmost) scope.
     */
    public function setVariable(string $name, string $className): void
    {
        $this->scopeStack[array_key_last($this->scopeStack)][$name] = $className;
    }

    /**
     * Resolves a variable name to its FQCN by searching from the innermost scope outward.
     * Returns null if the variable is not tracked.
     */
    public function getVariableType(string $name): ?string
    {
        for ($i = \count($this->scopeStack) - 1; $i >= 0; $i--) {
            if (isset($this->scopeStack[$i][$name])) {
                return $this->scopeStack[$i][$name];
            }
        }

        return null;
    }

    /**
     * Returns true if the variable is resolvable in any scope.
     */
    public function hasVariable(string $name): bool
    {
        return null !== $this->getVariableType($name);
    }
}
