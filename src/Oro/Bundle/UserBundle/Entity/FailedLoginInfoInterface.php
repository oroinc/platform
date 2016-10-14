<?php

namespace Oro\Bundle\UserBundle\Entity;

interface FailedLoginInfoInterface
{
    /**
     * Gets the last failed login time.
     *
     * @return \DateTime
     */
    public function getLastFailedLogin();

    /**
     * @param \DateTime $time New failed login time
     *
     * @return $this
     */
    public function setLastFailedLogin(\DateTime $time);

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
