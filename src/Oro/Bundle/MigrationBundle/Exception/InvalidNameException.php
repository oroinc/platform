<?php

namespace Oro\Bundle\MigrationBundle\Exception;

/**
 * Thrown when an invalid name is provided for a migration or fixture.
 *
 * This exception is raised when a bundle name, migration name, or fixture name does not meet
 * the required naming conventions or validation rules.
 */
class InvalidNameException extends \Exception
{
}
