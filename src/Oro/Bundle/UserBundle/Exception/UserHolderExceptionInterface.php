<?php

namespace Oro\Bundle\UserBundle\Exception;

/**
 * Exception that holds the user information.
 */
interface UserHolderExceptionInterface
{
    /**
     * @return mixed
     */
    public function getUser();

    /**
     * @param mixed $user
     */
    public function setUser($user): void;
}
