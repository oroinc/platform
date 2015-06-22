<?php

namespace Oro\Bundle\ReminderBundle\Model\Email;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\NotificationBundle\Processor\SenderAwareEmailNotificationInterface;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException;

class EmailNotification implements SenderAwareEmailNotificationInterface
{
    const TEMPLATE_ENTITY = 'Oro\Bundle\EmailBundle\Entity\EmailTemplate';
    const CONFIG_FIELD    = 'reminder_template_name';

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @var Reminder
     */
    protected $reminder;

    /**
     * @var EntityNameResolver
     */
    protected $entityNameResolver;

    /**
     * Constructor
     *
     * @param ObjectManager      $em
     * @param ConfigProvider     $configProvider
     * @param EntityNameResolver $entityNameResolver
     */
    public function __construct(
        ObjectManager $em,
        ConfigProvider $configProvider,
        EntityNameResolver $entityNameResolver
    ) {
        $this->em = $em;
        $this->configProvider = $configProvider;
        $this->entityNameResolver = $entityNameResolver;
    }

    /**
     * @param Reminder $reminder
     */
    public function setReminder(Reminder $reminder)
    {
        $this->reminder = $reminder;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        $className    = $this->getReminder()->getRelatedEntityClassName();
        $templateName = $this->configProvider
            ->getConfig($className)
            ->get(self::CONFIG_FIELD, true);

        return $this->loadTemplate($className, $templateName);
    }

    /**
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getEntity()
    {
        return $this->em
            ->getRepository($this->getReminder()->getRelatedEntityClassName())
            ->find($this->getReminder()->getRelatedEntityId());
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipientEmails()
    {
        return [$this->getReminder()->getRecipient()->getEmail()];
    }

    /**
     * {@inheritdoc}
     */
    public function getSenderEmail()
    {
        $sender = $this->getReminder()->getSender();
        return $sender ? $sender->getEmail() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSenderName()
    {
        $sender = $this->getReminder()->getSender();
        if ($sender) {
            return $this->entityNameResolver->getName($sender);
        }

        return null;
    }

    /**
     * @param string $className
     * @param string $templateName
     * @throws InvalidArgumentException
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

    /**
     * @return Reminder
     * @throws InvalidArgumentException
     */
    protected function getReminder()
    {
        if (!$this->reminder) {
            throw new InvalidArgumentException('Reminder was not set');
        }

        return $this->reminder;
    }
}
