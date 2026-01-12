<?php

namespace Oro\Bundle\UserBundle\Entity;

/**
 * Defines the contract for entities that track user login information.
 *
 * This interface provides methods to manage and retrieve login-related data
 * such as the last login timestamp and the total count of logins. Entities
 * implementing this interface can track user authentication history.
 */
interface LoginInfoInterface
{
    /**
     * Gets the last login time.
     *
     * @return \DateTime
     */
    public function getLastLogin();

    /**
     * Gets login count number.
     *
     * @return int
     */
    public function getLoginCount();

    /**
     * @param \DateTime $time New login time
     *
     * @return $this
     */
    public function setLastLogin(\DateTime $time);

    /**
     * @param int $count New login count value
     *
     * @return $this
     */
    public function setLoginCount($count);
}
