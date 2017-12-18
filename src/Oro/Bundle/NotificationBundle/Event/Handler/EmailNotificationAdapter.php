<?php

namespace Oro\Bundle\NotificationBundle\Event\Handler;

use Doctrine\ORM\EntityManager;

use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
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

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param object $entity
     * @param EmailNotification $notification
     * @param EntityManager $em
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(
        $entity,
        EmailNotification $notification,
        EntityManager $em,
        PropertyAccessor $propertyAccessor
    ) {
        $this->entity = $entity;
        $this->notification = $notification;
        $this->em = $em;
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
        $recipientList = $this->notification->getRecipientList();

        $emails = $this->em
            ->getRepository('Oro\Bundle\NotificationBundle\Entity\RecipientList')
            ->getRecipientEmails($recipientList, $this->entity);
        $emails = array_merge(
            $emails,
            $this->getRecipientEmailsFromAdditionalAssociations($this->entity, $recipientList)
        );
        $emails = array_merge(
            $emails,
            $this->getRecipientEmailsFromEntityEmails($this->entity, $recipientList)
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

    /**
     * Get array of emails by field marked as contact information with type "email"
     *
     * @param mixed         $entity
     * @param RecipientList $recipientList
     *
     * @return array
     */
    private function getRecipientEmailsFromEntityEmails($entity, RecipientList $recipientList)
    {
        $values = [];
        // Getting values from fields that marked as entity email
        foreach ($recipientList->getEntityEmails() as $propertyPath) {
            $values = array_merge($values, $this->getEntityEmailValue($entity, $propertyPath));
        }

        // Recursively leave only string values
        array_walk_recursive(
            $values,
            function (&$item) {
                if ($item instanceof EmailHolderInterface) {
                    $item = $item->getEmail();
                }
                if (!is_string($item)) {
                    $item = null;
                }
            }
        );

        // Flatten multidimensional array
        $emails = iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($values)), false);

        return array_filter($emails);
    }

    /**
     * Get email address value from entity by property path
     *
     * @param mixed  $entity
     * @param string $propertyPath
     *
     * @return array|\Traversable
     */
    private function getEntityEmailValue($entity, $propertyPath)
    {
        $value = [];
        if ($this->propertyAccessor->isReadable($entity, $propertyPath)) {
            $value = $this->propertyAccessor->getValue($entity, $propertyPath);
        }

        return is_array($value) || $value instanceof \Traversable ? $value : [$value];
    }
}
