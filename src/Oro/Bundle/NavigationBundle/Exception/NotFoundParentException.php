<?php

namespace Oro\Bundle\NavigationBundle\Exception;

/**
 * Thrown when a parent menu item cannot be found during menu operations.
 *
 * This exception is raised when attempting to perform operations on menu items that require
 * a valid parent item reference, but the parent cannot be located in the menu hierarchy.
 */
class NotFoundParentException extends \RuntimeException
{
}
