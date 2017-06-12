<?php

namespace Oro\Bundle\EntityExtendBundle\Exception;

use Oro\Bundle\EntityBundle\Exception\EntityExceptionInterface;
use Oro\Bundle\EntityBundle\Exception\RuntimeException;

/**
 * This exception is thrown when an entity is not valid for extend relation.
 */
class InvalidRelationEntityException extends RuntimeException implements EntityExceptionInterface
{
}
