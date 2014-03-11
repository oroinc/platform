<?php

namespace Oro\Bundle\ReminderBundle\Model\Email;

use Doctrine\Common\Persistence\ObjectManager;

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
        $entity    = $this->getEntity();
        $className = ClassUtils::getRealClass($entity);
        $config    = $this->configProvider->getConfig($className);

        $criteria = ['entityName' => $className];
        if ($config->has(self::CONFIG_FIELD)) {
            $criteria = ['name' => $config->get(self::CONFIG_FIELD)];
        }

        $repository = $this->em->getRepository(self::TEMPLATE_ENTITY);
        $template   = $repository->findOneBy($criteria);

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
        if (!$this->reminder) {
            throw new InvalidArgumentException('Reminder was not set');
        }

        return $this->em
            ->getRepository($this->reminder->getRelatedEntityClassName())
            ->find($this->reminder->getRelatedEntityId());
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipientEmails()
    {
        if (!$this->reminder) {
            throw new InvalidArgumentException('Reminder was not set');
        }

        return [$this->reminder->getRecipient()->getEmail()];
    }
}
