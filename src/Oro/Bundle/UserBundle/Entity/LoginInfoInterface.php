<?php

namespace Oro\Bundle\UserBundle\Entity;

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
