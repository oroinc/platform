<?php

namespace Oro\Bundle\ReminderBundle\Model\Email;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\EmailBundle\Entity\EmailTemplateTranslation;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\NotificationBundle\Processor\EmailNotificationInterface;
use Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException;

class EmailNotification implements EmailNotificationInterface
{
    const TEMPLATE_ENTITY = 'Oro\Bundle\EmailBundle\Entity\EmailTemplate';
    const CONFIG_FIELD = 'reminder_template_name';

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
     * Constructor
     *
     * @param ObjectManager  $em
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        ObjectManager $em,
        ConfigProvider $configProvider
    ) {
        $this->em = $em;
        $this->configProvider = $configProvider;
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
    public function getTemplate($locale = null)
    {
        $className    = $this->getReminder()->getRelatedEntityClassName();
        $templateName = $this->configProvider
            ->getConfig($className)
            ->get(self::CONFIG_FIELD, true);

        $template = $this->loadTemplate($className, $templateName);

        if (!is_null($locale)) {
            foreach ($template->getTranslations() as $translation) {
                /* @var EmailTemplateTranslation $translation */
                if ($locale == $translation->getLocale()) {
                    $template->{'set' . ucfirst($translation->getField())}($translation->getContent());
                }
            }
            $template->setLocale($locale);
        }

        return $template;
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
        return [$this->getReminder()->getOwner()->getEmail()];
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
