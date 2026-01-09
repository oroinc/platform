<?php

namespace Oro\Bundle\DataGridBundle\Exception;

/**
 * Thrown when a method is called incorrectly in datagrid components.
 */
class BadMethodCallException extends \BadMethodCallException implements DatagridException
{
}
