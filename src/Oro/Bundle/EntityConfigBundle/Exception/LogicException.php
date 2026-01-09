<?php

namespace Oro\Bundle\EntityConfigBundle\Exception;

/**
 * Thrown when an invalid or unexpected state is encountered in entity configuration logic.
 *
 * This exception indicates a programming error or violation of expected preconditions in the entity
 * configuration system, such as attempting to perform an operation that is logically invalid given
 * the current state of the configuration.
 */
class LogicException extends \RuntimeException
{
}
