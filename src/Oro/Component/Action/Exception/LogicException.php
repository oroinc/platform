<?php

namespace Oro\Component\Action\Exception;

/**
 * Thrown when a logic error occurs in action processing.
 *
 * This exception indicates a programming error or invalid state in the action framework,
 * such as attempting to execute an uninitialized action or violating action contracts.
 */
class LogicException extends \LogicException implements ExceptionInterface
{
}
