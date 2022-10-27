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
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var array */
    private $events;

    public function __construct(ManagerRegistry $doctrine, array $events)
    {
        $this->doctrine = $doctrine;
        $this->events = $events;
    }

    /**
     * @return array
     */
    public function getRecipientUsersChoices()
    {
        return $this->getEntityChoices(User::class, 'e.id, e.firstName, e.lastName');
    }

    /**
     * @return array
     */
    public function getRecipientGroupsChoices()
    {
        return $this->getEntityChoices(Group::class, 'e.id, e.name', 'name');
    }

    /**
     * @return array
     */
    public function getEventNameChoices()
    {
        return $this->events;
    }

    /**
     * @param string $entity
     * @param string $select
     * @param string $mainField
     *
     * @return array
     */
    private function getEntityChoices($entity, $select, $mainField = null)
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

    /**
     * @param string $entityClass
     *
     * @return EntityManagerInterface
     */
    private function getEntityManager($entityClass)
    {
        return $this->doctrine->getManagerForClass($entityClass);
    }
}
