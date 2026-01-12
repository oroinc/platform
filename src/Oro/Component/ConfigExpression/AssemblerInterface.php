<?php

namespace Oro\Component\ConfigExpression;

/**
 * Defines the contract for assembling expressions from configuration arrays.
 *
 * Implementations of this interface are responsible for converting configuration data
 * into executable expression objects. This is a core component of the expression system
 * that enables declarative configuration of complex business logic.
 */
interface AssemblerInterface
{
    /**
     * Builds the expression based on the given configuration.
     *
     * @param array $configuration
     *
     * @return ExpressionInterface|null
     */
    public function assemble(array $configuration);
}
