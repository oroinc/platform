<?php

namespace Oro\Bundle\UserBundle\Entity;

interface FailedLoginInfoInterface
{
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
}
