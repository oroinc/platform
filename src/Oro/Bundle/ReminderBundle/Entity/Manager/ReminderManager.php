<?php

namespace Oro\Bundle\ReminderBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Util\ClassUtils;

use Oro\Bundle\ReminderBundle\Exception\InvalidArgumentException;
use Oro\Bundle\ReminderBundle\Entity\Collection\RemindersPersistentCollection;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;
use Oro\Bundle\ReminderBundle\Entity\Reminder;

/**
 * Manages reminder persistence
 */
class ReminderManager
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Save reminders
     *
     * @param RemindableInterface $entity
     */
    public function saveReminders(RemindableInterface $entity)
    {
        $reminders = $entity->getReminders();

        if (!$reminders instanceof RemindersPersistentCollection) {
            foreach ($reminders as $reminder) {
                $this->syncEntityReminder($entity, $reminder);
                $this->entityManager->persist($reminder);
            }
        } else {
            if ($reminders->isDirty()) {
                foreach ($reminders->getInsertDiff() as $reminder) {
                    $this->entityManager->persist($reminder);
                }
                foreach ($reminders->getDeleteDiff() as $reminder) {
                    $this->entityManager->remove($reminder);
                }
            }
            foreach ($reminders as $reminder) {
                $this->syncEntityReminder($entity, $reminder);
            }
        }
    }

    /**
     * Sync reminder with entity data
     *
     * @param RemindableInterface $entity
     * @param Reminder $reminder
     */
    protected function syncEntityReminder(RemindableInterface $entity, Reminder $reminder)
    {
        $reminder->setReminderData($entity->getReminderData());
        $reminder->setRelatedEntityClassName($this->getEntityClassName($entity));
        $reminder->setRelatedEntityId($this->getEntityIdentifier($entity));
    }

    /**
     * Loads reminders into entity
     *
     * @param RemindableInterface $entity
     */
    public function loadReminders(RemindableInterface $entity)
    {
        $repository = $this->entityManager->getRepository('OroReminderBundle:Reminder');

        $reminders = new RemindersPersistentCollection(
            $repository,
            $this->getEntityClassName($entity),
            $this->getEntityIdentifier($entity)
        );

        $entity->setReminders($reminders);
    }

    /**
     * Gets entity class name
     *
     * @param RemindableInterface $entity
     * @return string
     */
    protected function getEntityClassName(RemindableInterface $entity)
    {
        return ClassUtils::getRealClass($entity);
    }

    /**
     * Gets single identifier of entity
     *
     * @param RemindableInterface $entity
     * @return mixed
     * @throws InvalidArgumentException If entity has multiple identifiers
     */
    protected function getEntityIdentifier(RemindableInterface $entity)
    {
        $className = $this->getEntityClassName($entity);
        $identifiers = $this->entityManager->getClassMetadata($className)->getIdentifierValues($entity);

        if (count($identifiers) > 1) {
            throw new InvalidArgumentException(
                sprintf(
                    'Entity "%s" with multiple identifiers "%s" is not supported by OroReminderBundle.',
                    $className,
                    implode('", "', $identifiers)
                )
            );
        }

        return current($identifiers);
    }
}
