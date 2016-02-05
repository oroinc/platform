<?php

namespace Oro\Bundle\CalendarBundle\Workflow\Action;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Model\ReminderInterval;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\Action\AbstractAction;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

/**
 * - @create_calendar_event:
 *     title: 'Interview'
 *     initiator: $currentUser
 *     guests: [$reviewer]
 *     start: $dateTime
 *     end: $dateTime
 *     attribute: $interview
 *     reminders:
 *         - method: email
 *           interval_number: 1
 *           interval_unit: H
 *         - method: web_socket
 *           interval_number: 10
 *           interval_unit: M
 */
class CreateCalendarEventAction extends AbstractAction
{
    const OPTION_KEY_TITLE       = 'title';
    const OPTION_KEY_DESCRIPTION = 'description';
    const OPTION_KEY_GUESTS      = 'guests';
    const OPTION_KEY_INITIATOR   = 'initiator';
    const OPTION_KEY_START       = 'start';
    const OPTION_KEY_END         = 'end';
    const OPTION_KEY_ATTRIBUTE   = 'attribute';
    const OPTION_KEY_REMINDERS   = 'reminders';

    const OPTION_REMINDER_KEY_METHOD          = 'method';
    const OPTION_REMINDER_KEY_INTERVAL_NUMBER = 'interval_number';
    const OPTION_REMINDER_KEY_INTERVAL_UNIT   = 'interval_unit';

    /**
     * @var array
     */
    private $options;

    /**
     * @var CalendarRepository
     */
    protected $calendarRepository;

    /**
     * @var ObjectManager
     */
    protected $manager;

    protected $requiredFields = [
        self::OPTION_KEY_TITLE,
        self::OPTION_KEY_INITIATOR,
        self::OPTION_KEY_START,
        self::OPTION_KEY_END,
    ];

    public function __construct(ContextAccessor $contextAccessor, Registry $registry)
    {
        $this->calendarRepository = $registry->getRepository('OroCalendarBundle:Calendar');
        $this->manager = $registry->getManagerForClass('Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        parent::__construct($contextAccessor);
    }

    /**
     * @inheritdoc
     */
    public function initialize(array $options)
    {
        $missingFields = array_diff($this->requiredFields, array_keys($options));

        if (0 !== count($missingFields)) {
            throw new InvalidParameterException(
                sprintf('Required fields "%s" must be filled', implode(', ', $missingFields))
            );
        }

        $this->options = $options;
    }

    /**
     * @inheritdoc
     */
    protected function executeAction($context)
    {
        $initiator = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_INITIATOR]);
        $start = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_START]);
        $end = $this->contextAccessor->getValue($context, $this->options[self::OPTION_KEY_END]);

        $calendarEvent = new CalendarEvent();
        $initiatorCalendar = $this->getDefaultUserCalendar($initiator);

        $calendarEvent
            ->setStart($start)
            ->setEnd($end)
            ->setTitle($this->options[self::OPTION_KEY_TITLE])
            ->setCalendar($initiatorCalendar)
            ->setAllDay(false)
        ;

        $this->addGuests($context, $calendarEvent);

        $this->manager->persist($calendarEvent);
        $this->manager->flush();

        $this->setReminders($calendarEvent);

        $this->manager->flush();

        if (isset($this->options[self::OPTION_KEY_ATTRIBUTE])) {
            $this->contextAccessor->setValue($context, $this->options[self::OPTION_KEY_ATTRIBUTE], $calendarEvent);
        }
    }

    /**
     * @param CalendarEvent $calendarEvent
     */
    protected function setReminders(CalendarEvent $calendarEvent)
    {
        if (false === isset($this->options[self::OPTION_KEY_REMINDERS])) {
            return;
        }

        $reminders = new ArrayCollection();

        foreach ($this->options[self::OPTION_KEY_REMINDERS] as $reminder) {
            $reminderEntity = new Reminder();
            $interval = new ReminderInterval(
                $reminder[self::OPTION_REMINDER_KEY_INTERVAL_NUMBER],
                $reminder[self::OPTION_REMINDER_KEY_INTERVAL_UNIT]
            );

            $reminderEntity
                ->setSubject($calendarEvent->getTitle())
                ->setExpireAt($calendarEvent->getStart())
                ->setMethod($reminder[self::OPTION_REMINDER_KEY_METHOD])
                ->setInterval($interval)
                ->setRelatedEntityClassName(get_class($calendarEvent))
                ->setRelatedEntityId($calendarEvent->getId())
                ->setRecipient($calendarEvent->getCalendar()->getOwner())
            ;

            $this->manager->persist($reminderEntity);
        }

        $calendarEvent->setReminders($reminders);

        foreach ($calendarEvent->getChildEvents() as $childEvent) {
            $this->setReminders($childEvent);
        }
    }

    /**
     * @param CalendarEvent $parentCalendarEvent
     * @param User $user
     * @return CalendarEvent
     */
    protected function createChildEvent(CalendarEvent $parentCalendarEvent, User $user)
    {
        $userCalendar = $this->getDefaultUserCalendar($user);

        $childCalendarEvent = new CalendarEvent();
        $childCalendarEvent
            ->setStart($parentCalendarEvent->getStart())
            ->setEnd($parentCalendarEvent->getEnd())
            ->setTitle($parentCalendarEvent->getTitle())
            ->setCalendar($userCalendar)
            ->setAllDay(false)
        ;

        $parentCalendarEvent->addChildEvent($childCalendarEvent);

        return $childCalendarEvent;
    }

    protected function getDefaultUserCalendar(User $user)
    {
        return $this->calendarRepository->findDefaultCalendar(
            $user->getId(),
            $user->getOrganization()->getId()
        );
    }

    /**
     * @param $context
     * @param CalendarEvent $calendarEvent
     */
    protected function addGuests($context, CalendarEvent $calendarEvent)
    {
        if (false === isset($this->options[self::OPTION_KEY_GUESTS])) {
            return;
        }

        foreach ($this->options[self::OPTION_KEY_GUESTS] as $guest) {
            $user = $this->contextAccessor->getValue($context, $guest);
            $this->createChildEvent($calendarEvent, $user);
        }
    }
}
