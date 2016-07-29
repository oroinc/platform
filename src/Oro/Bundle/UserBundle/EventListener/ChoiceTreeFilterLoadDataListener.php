<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\FilterBundle\Event\ChoiceTreeFilterLoadDataEvent;
use Oro\Bundle\UserBundle\Entity\User;

class ChoiceTreeFilterLoadDataListener
{
    const SUPPORTED_CLASS_NAME = 'Oro\Bundle\UserBundle\Entity\User';

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
