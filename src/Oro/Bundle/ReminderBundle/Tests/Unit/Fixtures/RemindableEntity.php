<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Fixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;
use Oro\Bundle\ReminderBundle\Model\ReminderData;
use Oro\Bundle\ReminderBundle\Model\ReminderDataInterface;

class RemindableEntity implements RemindableInterface
{
    /** @var ArrayCollection */
    protected $reminders;

    public function __construct()
    {
        $this->reminders = new ArrayCollection();
    }

    /**
     * @return Collection
     */
    public function getReminders()
    {
        return $this->reminders;
    }

    /**
     * @param Collection $reminders
     */
    public function setReminders(Collection $reminders)
    {
        $this->reminders = $reminders;
    }

    /**
     * @return ReminderDataInterface
     */
    public function getReminderData()
    {
        return new ReminderData();
    }
}
