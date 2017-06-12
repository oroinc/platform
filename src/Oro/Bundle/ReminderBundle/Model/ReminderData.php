<?php

namespace Oro\Bundle\ReminderBundle\Model;

use Oro\Bundle\UserBundle\Entity\User;

/**
 * Represents entity that provides information about reminder.
 */
class ReminderData implements ReminderDataInterface
{
    /**
     * @var string
     */
    protected $subject;

    /**
     * @var \DateTime
     */
    protected $expireAt;

    /**
     * @var User
     */
    protected $recipient;
    
    /**
     * @param string $subject
     * @return ReminderData
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param \DateTime $expireAt
     * @return ReminderData
     */
    public function setExpireAt($expireAt)
    {
        $this->expireAt = $expireAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpireAt()
    {
        return $this->expireAt;
    }

    /**
     * @param User $recipient
     * @return ReminderData
     */
    public function setRecipient($recipient)
    {
        $this->recipient = $recipient;

        return $this;
    }

    /**
     * @return User
     */
    public function getRecipient()
    {
        return $this->recipient;
    }
}
