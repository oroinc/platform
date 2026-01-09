<?php

namespace Oro\Component\Action\Exception;

/**
 * Thrown when a runtime error occurs during action execution.
 *
 * This exception is raised for unexpected runtime errors that occur during action processing,
 * such as database errors, service failures, or other runtime conditions that prevent execution.
 */
class RuntimeException extends \RuntimeException implements ExceptionInterface
{
}
