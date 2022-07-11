<?php

namespace Oro\Bundle\OrganizationBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FilterBundle\Event\ChoiceTreeFilterLoadDataEvent;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

/**
 * Loads data for a choice tree filter for BusinessUnit entity.
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
        if ($event->getClassName() === BusinessUnit::class) {
            $entities = $this->doctrine->getRepository($event->getClassName())->findBy(['id'=> $event->getValues()]);
            $data = [];
            /** @var BusinessUnit $entity */
            foreach ($entities as $entity) {
                $data[] = [
                    'id' => $entity->getId(),
                    'name' => $entity->getName(),
                    'treePath' => $this->getPath($entity, []),
                    'organization_id' => $entity->getOrganization()->getId()
                ];
            }
            $event->setData($data);
        }
    }

    protected function getPath(BusinessUnit $businessUnit, array $path): array
    {
        array_unshift($path, ['name'=> $businessUnit->getName()]);
        $owner = $businessUnit->getOwner();
        if ($owner) {
            $path = $this->getPath($owner, $path);
        } else {
            array_unshift($path, ['name'=> $businessUnit->getOrganization()->getName()]);
        }

        return $path;
    }
}
