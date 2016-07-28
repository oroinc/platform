<?php

namespace Oro\Bundle\CalendarBundle\Model\Email;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;

use Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException;
use Oro\Bundle\NotificationBundle\Processor\EmailNotificationInterface;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class EmailNotification implements EmailNotificationInterface
{
    const TEMPLATE_ENTITY = 'Oro\Bundle\EmailBundle\Entity\EmailTemplate';
    const ENTITY_CLASS_NAME = 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent';

    /** @var ObjectManager */
    protected $em;

    /** @var CalendarEvent */
    protected $calendarEvent;

    /** @var string */
    protected $templateName;

    /** @var array */
    protected $emails = [];

    /**
     * @param ObjectManager  $em
     */
    public function __construct(
        ObjectManager $em
    ) {
        $this->em = $em;
    }

    /**
     * @param CalendarEvent $calendarEvent
     */
    public function setCalendarEvent(CalendarEvent $calendarEvent)
    {
        $this->calendarEvent = $calendarEvent;
    }

    /**
     * @param string $templateName
     */
    public function setTemplateName($templateName)
    {
        $this->templateName = $templateName;
    }

    /**
     * @param $emails
     */
    public function setEmails($emails)
    {
        $this->emails = $emails;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->loadTemplate(static::ENTITY_CLASS_NAME, $this->templateName);
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipientEmails()
    {
        return $this->emails;
    }

    /**
     * @return CalendarEvent
     */
    public function getEntity()
    {
        return $this->calendarEvent;
    }

    /**
     * @param string $className
     * @param string $templateName
     * @throws InvalidArgumentException
     *
     * @return EmailTemplate
     */
    protected function loadTemplate($className, $templateName)
    {
        $repository = $this->em->getRepository(self::TEMPLATE_ENTITY);
        $templates  = $repository->findBy(array('entityName' => $className, 'name' => $templateName));

        if (!$templates) {
            throw new InvalidArgumentException(
                sprintf('Template with name "%s" for "%s" not found', $templateName, $className)
            );
        }

        if (count($templates) > 1) {
            throw new InvalidArgumentException(
                sprintf('Multiple templates with name "%s" for "%s" found', $templateName, $className)
            );
        }

        return reset($templates);
    }
}
