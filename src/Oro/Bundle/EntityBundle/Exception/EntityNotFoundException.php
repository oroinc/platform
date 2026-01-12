<?php

namespace Oro\Bundle\EntityBundle\Exception;

use Doctrine\ORM\EntityNotFoundException as DoctrineEntityNotFoundExceptionException;

/**
 * Thrown when a requested entity cannot be found in the database.
 *
 * This exception extends Doctrine's EntityNotFoundException and provides a way to
 * set a custom error message for entity lookup failures.
 */
class EntityNotFoundException extends DoctrineEntityNotFoundExceptionException implements EntityExceptionInterface
{
    /**
     * @param string|null $message
     */
    public function __construct($message = null)
    {
        parent::__construct();
        if ($message) {
            $this->message = $message;
        }
    }
}
