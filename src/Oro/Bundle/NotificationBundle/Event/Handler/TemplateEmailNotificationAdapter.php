<?php

namespace Oro\Bundle\NotificationBundle\Event\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\NotificationBundle\Entity\EmailNotification;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\NotificationBundle\Event\NotificationProcessRecipientsEvent;
use Oro\Bundle\NotificationBundle\Helper\WebsiteAwareEntityHelper;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\NotificationBundle\Model\TemplateEmailNotificationInterface;
use Oro\Bundle\NotificationBundle\Provider\ChainAdditionalEmailAssociationProvider;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Adapts EmailNotification entity to TemplateEmailNotificationInterface getting recipients from the related recipient
 * list.
 */
class TemplateEmailNotificationAdapter implements TemplateEmailNotificationInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EmailNotification
     */
    private $notification;

    /**
     * @var object
     */
    private $entity;

    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /** @var ChainAdditionalEmailAssociationProvider */
    private $additionalEmailAssociationProvider;

    private WebsiteAwareEntityHelper $websiteAwareEntityHelper;

    /**
     * @param object                                  $entity
     * @param EmailNotification                       $notification
     * @param EntityManager                           $entityManager
     * @param PropertyAccessorInterface               $propertyAccessor
     * @param EventDispatcherInterface                $eventDispatcher
     * @param ChainAdditionalEmailAssociationProvider $additionalEmailAssociationProvider
     */
    public function __construct(
        $entity,
        EmailNotification $notification,
        EntityManager $entityManager,
        PropertyAccessorInterface $propertyAccessor,
        EventDispatcherInterface $eventDispatcher,
        ChainAdditionalEmailAssociationProvider $additionalEmailAssociationProvider,
        WebsiteAwareEntityHelper $websiteAwareEntityHelper,
    ) {
        $this->entity = $entity;
        $this->notification = $notification;
        $this->entityManager = $entityManager;
        $this->propertyAccessor = $propertyAccessor;
        $this->eventDispatcher = $eventDispatcher;
        $this->additionalEmailAssociationProvider = $additionalEmailAssociationProvider;
        $this->websiteAwareEntityHelper = $websiteAwareEntityHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateCriteria(): EmailTemplateCriteria
    {
        return new EmailTemplateCriteria(
            $this->notification->getTemplate()->getName(),
            $this->notification->getTemplate()->getEntityName()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(): iterable
    {
        $recipientList = $this->notification->getRecipientList();

        $recipients = $this->entityManager
            ->getRepository(RecipientList::class)
            ->getRecipients($recipientList);

        $recipients = $this->uniqueHolders(array_merge(
            $recipients,
            $this->getRecipientsFromAdditionalAssociations($this->entity, $recipientList)
        ));

        $recipients = $this->uniqueHolders(array_merge(
            $recipients,
            $this->getRecipientsFromEntityEmails($this->entity, $recipientList)
        ));

        $this->removeDisabledUsers($recipients);

        $event = new NotificationProcessRecipientsEvent($this->entity, $recipients, $this->websiteAwareEntityHelper);
        $this->eventDispatcher->dispatch($event, NotificationProcessRecipientsEvent::NAME);

        return $event->getRecipients();
    }

    protected function removeDisabledUsers(array &$recipients)
    {
        foreach ($recipients as $key => $recipient) {
            if (($recipient instanceof AbstractUser && !$recipient->isEnabled())
                || ($recipient instanceof EmailAddressWithContext
                    && $recipient->getContext() instanceof AbstractUser
                    && !$recipient->getContext()->isEnabled())
            ) {
                unset($recipients[$key]);
            }
        }
    }

    /**
     * Get array of emails by field marked as contact information with type "email"
     *
     * @param mixed         $entity
     * @param RecipientList $recipientList
     *
     * @return array
     */
    private function getRecipientsFromEntityEmails($entity, RecipientList $recipientList): array
    {
        $values = [];
        // Getting values from fields that marked as entity email
        foreach ($recipientList->getEntityEmails() as $propertyPath) {
            $values = array_merge($values, $this->getEntityEmailValue($entity, $propertyPath));
        }

        $recipients = [];
        // Recursively leave only string values
        array_walk_recursive(
            $values,
            function (&$item) use (&$recipients) {
                if ($item instanceof EmailHolderInterface && !empty($item->getEmail())) {
                    $recipients[] = $item;
                }

                if (!is_string($item)) {
                    $item = null;
                }
            }
        );

        $usedEmails = [];
        foreach ($recipients as $recipient) {
            $usedEmails[$recipient->getEmail()] = true;
        }

        // Flatten multidimensional array
        $emails = iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($values)), false);
        $emails = array_filter($emails);

        foreach ($emails as $email) {
            if (!empty($usedEmails[$email])) {
                continue;
            }

            $recipients[] = new EmailAddressWithContext($email);
        }

        return $this->uniqueHolders($recipients);
    }

    /**
     * @param object $entity
     * @param RecipientList $recipientList
     * @return array
     */
    private function getRecipientsFromAdditionalAssociations($entity, RecipientList $recipientList): array
    {
        $entities = [];
        foreach ($recipientList->getAdditionalEmailAssociations() as $association) {
            $associationComponents = explode('.', $association);

            $associationEntities = [$entity];
            foreach ($associationComponents as $associationComponent) {
                $newEntities = [];
                foreach ($associationEntities as $associationEntity) {
                    $subEntities = $this->additionalEmailAssociationProvider
                        ->getAssociationValue($associationEntity, $associationComponent);
                    $subEntities = is_array($subEntities) || $subEntities instanceof \Traversable
                        ? $subEntities
                        : [$subEntities];
                    if (!is_array($subEntities)) {
                        $subEntities = iterator_to_array($subEntities);
                    }
                    $newEntities = array_merge($newEntities, array_filter($subEntities));
                }
                $associationEntities = $newEntities;
            }

            $entities = array_merge($entities, $associationEntities);
        }

        $entities = $this->uniqueHolders($entities);

        return array_filter($entities, function ($entity) {
            /** @var EmailHolderInterface $entity */
            return !empty($entity->getEmail());
        });
    }

    /**
     * @param array|EmailHolderInterface[] $holders
     * @return array|EmailHolderInterface[]
     */
    private function uniqueHolders(array $holders): array
    {
        $addresses = [];
        $uniqueHolders = [];
        foreach ($holders as $holder) {
            $holderClass = \get_class($holder);
            if (isset($addresses[$holderClass][$holder->getEmail()])) {
                continue;
            }

            $uniqueHolders[] = $holder;
            $addresses[$holderClass][$holder->getEmail()] = true;
        }

        return $uniqueHolders;
    }

    /**
     * Get email address value from entity by property path
     *
     * @param mixed  $entity
     * @param string $propertyPath
     *
     * @return iterable
     */
    private function getEntityEmailValue($entity, $propertyPath): iterable
    {
        $value = [];
        if ($this->propertyAccessor->isReadable($entity, $propertyPath)) {
            $value = $this->propertyAccessor->getValue($entity, $propertyPath);
        }

        return is_array($value) || $value instanceof \Traversable ? $value : [$value];
    }
}
