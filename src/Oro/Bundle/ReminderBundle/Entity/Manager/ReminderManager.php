<?php

namespace Oro\Bundle\ReminderBundle\Entity\Manager;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ReminderBundle\Entity\Collection\RemindersPersistentCollection;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;
use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\ReminderBundle\Entity\Repository\ReminderRepository;
use Oro\Bundle\ReminderBundle\Model\ReminderDataInterface;

/**
 * Manages reminder persistence
 */
class ReminderManager
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * Save reminders
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function saveReminders(RemindableInterface $entity)
    {
        $entityId = $this->doctrineHelper->getSingleEntityIdentifier($entity);
        if (!$entityId) {
            return;
        }

        $reminders = $entity->getReminders();
        $entityClass = $this->doctrineHelper->getEntityClass($entity);

        $em = $this->doctrineHelper->getEntityManager(Reminder::class);

        $persist = true;
        if ($reminders instanceof RemindersPersistentCollection) {
            if ($reminders->isDirty()) {
                foreach ($reminders->getInsertDiff() as $reminder) {
                    $em->persist($reminder);
                }
                foreach ($reminders->getDeleteDiff() as $reminder) {
                    $em->remove($reminder);
                }
            }
            $persist = false;
        }

        $reminders = is_array($reminders) ? new ArrayCollection($reminders) : $reminders;

        if ($reminders instanceof Collection) {
            if (!$reminders->isEmpty()) {
                $reminderData = $entity->getReminderData();
                foreach ($reminders as $reminder) {
                    $this->syncEntityReminder($reminder, $reminderData, $entityClass, $entityId);
                    if ($persist) {
                        $em->persist($reminder);
                    }
                }
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
     */
    public function loadReminders(RemindableInterface $entity)
    {
        $reminders = new RemindersPersistentCollection(
            $this->getRemindersRepository(),
            $this->doctrineHelper->getEntityClass($entity),
            $this->doctrineHelper->getSingleEntityIdentifier($entity)
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
            || !is_subclass_of($entityClassName, RemindableInterface::class)
        ) {
            return;
        }

        $reminders = $this->getRemindersRepository()
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
     * @return ReminderRepository
     */
    protected function getRemindersRepository()
    {
        return $this->doctrineHelper
            ->getEntityManager(Reminder::class)
            ->getRepository(Reminder::class);
    }
}
