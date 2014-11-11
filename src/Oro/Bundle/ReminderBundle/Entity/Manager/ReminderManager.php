<?php

namespace Oro\Bundle\ReminderBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ReminderBundle\Model\ReminderDataInterface;
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
        // Persist and flush new entity to get id
        if (!$entityId = $this->getEntityIdentifier($entity)) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush($entity);

            $entityId = $this->getEntityIdentifier($entity);
        }

        $reminders    = $entity->getReminders();
        $reminderData = $entity->getReminderData();
        $entityClass  = $this->getEntityClassName($entity);

        if (!$reminders instanceof RemindersPersistentCollection) {
            foreach ($reminders as $reminder) {
                $this->syncEntityReminder($reminder, $reminderData, $entityClass, $entityId);
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
                $this->syncEntityReminder($reminder, $reminderData, $entityClass, $entityId);
            }
        }
    }

    /**
     * Sync reminder with entity data
     *
     * @param Reminder              $reminder
     * @param ReminderDataInterface $reminderData
     * @param string                $entityClass
     * @param mixed                 $entityId
     */
    protected function syncEntityReminder(
        Reminder $reminder,
        ReminderDataInterface $reminderData,
        $entityClass,
        $entityId
    ) {
        $reminder->setReminderData($reminderData);
        $reminder->setRelatedEntityClassName($entityClass);
        $reminder->setRelatedEntityId($entityId);
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
     * Finds reminders for the given entities of the given type
     * and sets them to each item
     *
     * @param array  $items
     * @param string $entityClassName
     */
    public function applyReminders(array &$items, $entityClassName)
    {
        if (empty($items)
            || !is_subclass_of($entityClassName, 'Oro\Bundle\ReminderBundle\Entity\RemindableInterface')
        ) {
            return;
        }

        $repository       = $this->entityManager->getRepository('OroReminderBundle:Reminder');
        $reminders        = $repository
            ->findRemindersByEntitiesQueryBuilder($entityClassName, $this->extractProperty($items, 'id'))
            ->select('reminder.relatedEntityId, reminder.method, reminder.intervalNumber, reminder.intervalUnit')
            ->getQuery()
            ->getArrayResult();
        $groupedReminders = [];
        foreach ($reminders as $reminder) {
            $groupedReminders[$reminder['relatedEntityId']][] = $reminder;
        }

        foreach ($items as &$item) {
            if (isset($groupedReminders[$item['id']])) {
                foreach ($groupedReminders[$item['id']] as $reminder) {
                    $item['reminders'][] = [
                        'method'   => $reminder['method'],
                        'interval' => [
                            'number' => $reminder['intervalNumber'],
                            'unit'   => $reminder['intervalUnit']
                        ]
                    ];
                }
            }
        }
    }

    /**
     * @param array  $items
     * @param string $property
     *
     * @return array
     */
    protected function extractProperty(array $items, $property)
    {
        return array_map(
            function ($item) use ($property) {
                return $item[$property];
            },
            $items
        );
    }

    /**
     * Gets entity class name
     *
     * @param RemindableInterface $entity
     *
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
     *
     * @return mixed
     * @throws InvalidArgumentException If entity has multiple identifiers
     */
    protected function getEntityIdentifier(RemindableInterface $entity)
    {
        $className   = $this->getEntityClassName($entity);
        $identifiers = $this->entityManager->getClassMetadata($className)->getIdentifierValues($entity);

        if (count($identifiers) > 1) {
            throw new InvalidArgumentException(
                sprintf(
                    'Entity "%s" with multiple identifiers "%s" is not supported by OroReminderBundle.',
                    $className,
                    implode('", "', array_keys($identifiers))
                )
            );
        }

        return current($identifiers);
    }
}
