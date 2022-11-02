<?php

namespace Oro\Bundle\NavigationBundle\Entity;

/**
 * Navigation Entity Interface
 */
interface NavigationItemInterface
{
    /**
     * Get id
     *
     * @return integer
     */
    public function getId();

    /**
     * Get user
     *
     * @return \Oro\Bundle\UserBundle\Entity\User
     */
    public function getUser();

    /**
     * Set entity properties
     */
    public function setValues(array $values);
}
