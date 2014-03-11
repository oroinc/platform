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
     * @var string
     */
    protected $relatedRouteName;

    /**
     * @var array
     */
    protected $relatedRouteParameters;

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

    /**
     * @param string $relatedRouteName
     * @return ReminderData
     */
    public function setRelatedRouteName($relatedRouteName)
    {
        $this->relatedRouteName = $relatedRouteName;

        return $this;
    }

    /**
     * @return string
     */
    public function getRelatedRouteName()
    {
        return $this->relatedRouteName;
    }

    /**
     * @param array $relatedRouteParameters
     * @return ReminderData
     */
    public function setRelatedRouteParameters($relatedRouteParameters)
    {
        $this->relatedRouteParameters = $relatedRouteParameters;

        return $this;
    }

    /**
     * @return array
     */
    public function getRelatedRouteParameters()
    {
        return $this->relatedRouteParameters;
    }
}
