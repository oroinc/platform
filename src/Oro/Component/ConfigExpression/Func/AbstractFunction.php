<?php

namespace Oro\Component\ConfigExpression\Func;

use Oro\Component\ConfigExpression\AbstractExpression;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;

/**
 * Provides common functionality for configuration expression functions.
 *
 * This base class extends {@see AbstractExpression} and adds context accessor support,
 * allowing functions to access and manipulate context data.
 * Subclasses should implement the doEvaluate method to define the specific function logic.
 */
abstract class AbstractFunction extends AbstractExpression implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;
}
