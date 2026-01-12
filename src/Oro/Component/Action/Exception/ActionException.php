<?php

namespace Oro\Component\Action\Exception;

/**
 * Thrown when an error occurs during action execution.
 *
 * This exception is raised when an action fails to execute properly, such as when entity
 * persistence fails or other runtime errors occur during action processing.
 */
class ActionException extends \Exception
{
}
