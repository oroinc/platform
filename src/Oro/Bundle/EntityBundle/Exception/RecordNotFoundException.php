<?php

namespace Oro\Bundle\EntityBundle\Exception;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Thrown when a requested record cannot be found, resulting in a 404 HTTP response.
 *
 * This exception extends Symfony's {@see NotFoundHttpException} and is used when an entity
 * or record lookup fails and should result in a `404 Not Found HTTP response`.
 */
class RecordNotFoundException extends NotFoundHttpException
{
}
