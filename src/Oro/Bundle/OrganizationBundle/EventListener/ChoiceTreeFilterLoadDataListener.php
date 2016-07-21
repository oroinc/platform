<?php

namespace Oro\Bundle\OrganizationBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\FilterBundle\Event\ChoiceTreeFilterLoadDataEvent;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;

class ChoiceTreeFilterLoadDataListener
{
    const SUPPORTED_CLASS_NAME = 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit';

    /** @var Registry */
    protected $doctrine;

    /**
     * IndexerPrepareQueryListener constructor.
     *
     * @param Registry $doctrine
     */
    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param ChoiceTreeFilterLoadDataEvent $event
     */
    public function fillData(ChoiceTreeFilterLoadDataEvent $event)
    {
        if ($event->getClassName() === static::SUPPORTED_CLASS_NAME) {
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

    /**
     * @param BusinessUnit $businessUnit
     * @param $path
     *
     * @return mixed
     */
    protected function getPath($businessUnit, $path)
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
