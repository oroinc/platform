<?php

namespace Oro\Bundle\NotificationBundle\Event\Handler;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\NotificationBundle\Model\EmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;

/**
 * Adapts handler data to EmailNotificationInterface required for email notifications processor
 */
class EmailNotificationAdapter implements EmailNotificationInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var EmailNotification */
    protected $notification;

    /** @var object */
    protected $entity;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param object $entity
     * @param EmailNotification $notification
     * @param EntityManager $em
     * @param ConfigProvider $configProvider
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(
        $entity,
        EmailNotification $notification,
        EntityManager $em,
        ConfigProvider $configProvider,
        PropertyAccessor $propertyAccessor
    ) {
        $this->entity = $entity;
        $this->notification = $notification;
        $this->em = $em;
        $this->configProvider = $configProvider;
        $this->propertyAccessor = $propertyAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->notification->getTemplate();
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
        $recipientList = $this->notification->getRecipientList();

        $emails = $this->em
            ->getRepository('Oro\Bundle\NotificationBundle\Entity\RecipientList')
            ->getRecipientEmails($recipientList, $this->entity, $ownerFieldName);
        $emails = array_merge(
            $emails,
            $this->getRecipientEmailsFromAdditionalAssociations($this->entity, $recipientList)
        );

        return array_unique($emails);
    }

    /**
     * @param object $entity
     * @param RecipientList $recipientList
     * @return array
     */
    private function getRecipientEmailsFromAdditionalAssociations($entity, RecipientList $recipientList)
    {
        $entities = [];
        foreach ($recipientList->getAdditionalEmailAssociations() as $association) {
            $associationComponents = explode('.', $association);

            $associationEntities = [$entity];
            foreach ($associationComponents as $associationComponent) {
                $newEntities = [];
                foreach ($associationEntities as $associationEntity) {
                    $subEntities = $this->propertyAccessor->getValue($associationEntity, $associationComponent);
                    $subEntities = is_array($subEntities) || $subEntities instanceof \Traversable ?
                        $subEntities : [$subEntities];
                    if (!is_array($subEntities)) {
                        $subEntities = iterator_to_array($subEntities);
                    }
                    $newEntities = array_merge($newEntities, array_filter($subEntities));
                }
                $associationEntities = $newEntities;
            }

            $entities = array_merge($entities, $associationEntities);
        }

        $emails = array_map(
            function (EmailHolderInterface $entity) {
                return $entity->getEmail();
            },
            $entities
        );

        return array_filter($emails);
    }
}
