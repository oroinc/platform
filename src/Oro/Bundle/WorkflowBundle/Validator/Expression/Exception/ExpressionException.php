<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Expression\Exception;

/**
 * Thrown when an expression validation fails.
 *
 * This exception indicates that an expression (such as a cron expression) is invalid
 * or cannot be parsed.
 */
class ExpressionException extends \InvalidArgumentException
{
}
