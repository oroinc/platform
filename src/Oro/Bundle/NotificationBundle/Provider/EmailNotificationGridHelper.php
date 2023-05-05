<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Provides a set of utility methods for email notification grids.
 */
class EmailNotificationGridHelper
{
    private ManagerRegistry $doctrine;
    /** @var string[] */
    private array $events;

    public function __construct(ManagerRegistry $doctrine, array $events)
    {
        $this->doctrine = $doctrine;
        $this->events = $events;
    }

    public function getRecipientUsersChoices(): array
    {
        return $this->getEntityChoices(User::class, 'e.id, e.firstName, e.lastName');
    }

    public function getRecipientGroupsChoices(): array
    {
        return $this->getEntityChoices(Group::class, 'e.id, e.name', 'name');
    }

    public function getEventNameChoices(): array
    {
        return array_combine($this->events, $this->events);
    }

    private function getEntityChoices(string $entity, string $select, ?string $mainField = null): array
    {
        $options = [];
        $entities = $this->getEntityManager($entity)
            ->createQueryBuilder()
            ->from($entity, 'e')
            ->select($select)
            ->getQuery()
            ->getArrayResult();
        foreach ($entities as $entityItem) {
            $id = $entityItem['id'];
            if (null === $mainField) {
                unset($entityItem['id']);
                $options[implode(' ', $entityItem)] = $id;
            } else {
                $options[$entityItem[$mainField]] = $id;
            }
        }

        return $options;
    }

    private function getEntityManager(string $entityClass): EntityManagerInterface
    {
        return $this->doctrine->getManagerForClass($entityClass);
    }
}
