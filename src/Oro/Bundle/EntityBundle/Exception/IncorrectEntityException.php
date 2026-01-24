<?php

namespace Oro\Bundle\EntityBundle\Exception;

/**
 * Thrown when an entity does not meet expected requirements or constraints.
 *
 * This exception indicates that an entity is invalid or does not conform to
 * the expected structure or state for the operation being performed.
 */
class IncorrectEntityException extends \LogicException implements EntityExceptionInterface
{
}
