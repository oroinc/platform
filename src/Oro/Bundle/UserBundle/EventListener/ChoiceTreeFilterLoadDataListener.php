<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Event\ChoiceTreeFilterLoadDataEvent;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * Loads data for a choice tree filter for User entity.
 */
class ChoiceTreeFilterLoadDataListener
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function fillData(ChoiceTreeFilterLoadDataEvent $event): void
    {
        if ($event->getClassName() === User::class) {
            $entities = $this->doctrine->getRepository($event->getClassName())->findBy(['id' => $event->getValues()]);
            $data = [];
            /** @var User $entity */
            foreach ($entities as $entity) {
                $data[] = [
                    'id' => $entity->getId(),
                    'fullName' => $entity->getFullName()
                ];
            }
            $event->setData($data);
        }
    }
}
