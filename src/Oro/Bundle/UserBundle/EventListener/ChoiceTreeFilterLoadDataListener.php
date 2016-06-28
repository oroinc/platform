<?php

namespace Oro\Bundle\UserBundle\EventListener;

use Oro\Bundle\EntityBundle\ORM\Registry;

use Oro\Bundle\FilterBundle\Event\ChoiceTreeFilterLoadDataEvent;
use Oro\Bundle\UserBundle\Entity\User;

class ChoiceTreeFilterLoadDataListener
{
    const EXPECTED_CLASS_NAME = 'Oro\Bundle\UserBundle\Entity\User';

    /** @var Registry */
    protected $doctrine;

    /**
     * IndexerPrepareQueryListener constructor.
     *
     * @param Registry $doctrine
     */
    public function __construct(
        Registry $doctrine
    ) {
        $this->doctrine = $doctrine;
    }


    public function fillData(ChoiceTreeFilterLoadDataEvent $event)
    {
        if ($event->getClassName() === static::EXPECTED_CLASS_NAME) {
            $event->getValues();

            $entities = $this->doctrine->getRepository($event->getClassName())->findBy(['id'=> $event->getValues()]);

            $data = [];
            /** @var User $entity */
            foreach ($entities as $entity) {
                $result = [];
                $result['id'] = $entity->getId();
                $result['fullName'] = $entity->getFullName();
                $data[] = $result;
            }

            $event->setData($data);
        }
    }
}
