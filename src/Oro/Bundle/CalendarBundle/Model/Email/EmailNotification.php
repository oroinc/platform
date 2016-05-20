<?php

namespace Oro\Bundle\CalendarBundle\Model\Email;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;

use Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException;
use Oro\Bundle\NotificationBundle\Processor\EmailNotificationInterface;
use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;

class EmailNotification implements EmailNotificationInterface
{
    const TEMPLATE_ENTITY = 'Oro\Bundle\EmailBundle\Entity\EmailTemplate';

    /** @var ObjectManager */
    protected $em;

    /** @var Attendee|CalendarEvent */
    protected $entity;

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
     * @param Attendee|CalendarEvent $entity
     */
    public function setEntity($entity)
    {
        if ($entity instanceof CalendarEvent && $entity instanceof Attendee) {
            throw new \InvalidArgumentException(sprintf(
                '$entity needs to be one of: %s, %s but %s given',
                'Oro\Bundle\CalendarBundle\Entity\Attendee',
                'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                ClassUtils::getClass($entity)
            ));
        }

        $this->entity = $entity;
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
        return $this->loadTemplate(ClassUtils::getClass($this->entity), $this->templateName);
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipientEmails()
    {
        return $this->emails;
    }

    public function getEntity()
    {
        return $this->entity;
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
