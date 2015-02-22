<?php

namespace Oro\Component\ConfigExpression;

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
