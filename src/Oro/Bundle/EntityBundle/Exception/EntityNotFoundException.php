<?php

namespace Oro\Bundle\EntityBundle\Exception;

use Doctrine\ORM\EntityNotFoundException as DoctrineEntityNotFoundExceptionException;

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
