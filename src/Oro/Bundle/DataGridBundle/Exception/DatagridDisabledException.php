<?php

declare(strict_types=1);

namespace Oro\Bundle\DataGridBundle\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Thrown when a datagrid is disabled because a feature this datagrid belongs to is disabled.
 *
 * This exception extends Symfony's {@see NotFoundHttpException}, so datagrid endpoints respond with 404
 * when a datagrid is disabled by a feature.
 */
class DatagridDisabledException extends NotFoundHttpException implements DatagridException
{
}
