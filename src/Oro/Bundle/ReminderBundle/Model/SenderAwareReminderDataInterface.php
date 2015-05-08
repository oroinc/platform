<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\UserBundle\Entity\User;

interface SenderAwareReminderDataInterface extends ReminderDataInterface
{
    /**
     * @return User
     */
    public function getSender();
}
