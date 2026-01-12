<?php

namespace Oro\Bundle\IntegrationBundle\Exception;

/**
 * Thrown when an integration operation violates logical constraints or preconditions.
 *
 * This exception is raised when an operation cannot be performed due to invalid state,
 * missing dependencies, or other logical errors in the integration workflow.
 */
class LogicException extends \LogicException implements IntegrationException
{
}
