<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * Represents entity that provides information about reminder.
 */
interface ReminderDataInterface
{
    /**
     * @return string
     */
    public function getSubject();

    /**
     * @return \DateTime
     */
    public function getExpireAt();

    /**
     * @return User
     */
    public function getRecipient();

    /**
     * @return string
     */
    public function getRelatedRouteName();

    /**
     * @return array
     */
    public function getRelatedRouteParameters();
}
