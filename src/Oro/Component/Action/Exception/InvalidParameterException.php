<?php

namespace Oro\Component\Action\Exception;

/**
 * Thrown when an action parameter is invalid or missing.
 *
 * This exception is raised when an action receives invalid parameters during initialization,
 * such as missing required parameters, incorrect parameter types, or invalid property paths.
 */
class InvalidParameterException extends InvalidArgumentException
{
}
