<?php

namespace Oro\Bundle\NotificationBundle\Event\Handler;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\NotificationBundle\Processor\EmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;

/**
 * Adapts handler data to EmailNotificationInterface required for email notifications processor
 */
class EmailNotificationAdapter implements EmailNotificationInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var EmailNotification
     */
    protected $notification;

    /**
     * @var mixed
     */
    protected $entity;

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * Constructor
     *
     * @param mixed             $entity
     * @param EmailNotification $notification
     * @param EntityManager     $em
     * @param ConfigProvider    $configProvider
     */
    public function __construct(
        $entity,
        EmailNotification $notification,
        EntityManager $em,
        ConfigProvider $configProvider
    ) {
        $this->entity         = $entity;
        $this->notification   = $notification;
        $this->em             = $em;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate($locale = null)
    {
        $template = $this->notification->getTemplate();

        if (!is_null($locale)) {
            foreach ($template->getTranslations() as $translation) {
                if ($locale == $translation->getLocale()) {
                    $template->{'set' . ucfirst($translation->getField())}($translation->getContent());
                }
            }
        }
        $template->setLocale($locale);

        return $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipientEmails()
    {
        $class = ClassUtils::getClass($this->entity);
        $ownerFieldName = $this->configProvider->hasConfig($class) ?
            $this->configProvider->getConfig($class)->get('owner_field_name') :
            null;

        return $this->em
            ->getRepository('Oro\Bundle\NotificationBundle\Entity\RecipientList')
            ->getRecipientEmails($this->notification->getRecipientList(), $this->entity, $ownerFieldName);
    }
}
