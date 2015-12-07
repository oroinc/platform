<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class CalendarEventGuestApiEntityManager extends ApiEntityManager
{
    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /**
     * @param string             $class
     * @param ObjectManager      $om
     * @param EntityNameResolver $resolver
     */
    public function __construct($class, ObjectManager $om, EntityNameResolver $resolver)
    {
        parent::__construct($class, $om);
        $this->entityNameResolver = $resolver;
    }

    /**
     * {@inheritdoc}
     */
    public function getListQueryBuilder($limit = 10, $page = 1, $criteria = [], $orderBy = null, $joins = [])
    {
        $userNameDQL = $this->entityNameResolver->getNameDQL('Oro\Bundle\UserBundle\Entity\User', 'u');
        $criteria    = $this->prepareQueryCriteria($limit ? : null, $page, $criteria, $orderBy);

        return $this->getRepository()->createQueryBuilder('e')
            ->select('e.id, e.invitationStatus, u.email,' . sprintf('%s AS userFullName', $userNameDQL))
            ->join('e.calendar', 'c')
            ->join('c.owner', 'u')
            ->addCriteria($criteria);
    }
}
