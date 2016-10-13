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


    /**
     * @param int $count New failed login count value
     *
     * @return $this
     */
    public function setFailedLoginCount($count);

    /**
     * @return int
     */
    public function getFailedLoginCount();

    /**
     * @param int $count New daily failed login count value
     *
     * @return $this
     */
    public function setDailyFailedLoginCount($count);

    /**
     * @return int
     */
    public function getDailyFailedLoginCount();
}
