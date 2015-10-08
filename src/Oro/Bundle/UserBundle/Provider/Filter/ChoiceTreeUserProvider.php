<?php

namespace Oro\Bundle\UserBundle\Provider\Filter;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;

class ChoiceTreeUserProvider
{
    /** @var Registry */
    protected $registry;

    /** @var AclHelper */
    protected $aclHelper;

    /**
     * @param Registry $registry
     * @param AclHelper $aclHelper
     */
    public function __construct(Registry $registry, AclHelper $aclHelper)
    {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
    }

    /**
     * @return array
     */
    public function getList()
    {
        $response = [];
        $qb = $this->registry->getManager()->getRepository('OroUserBundle:User')->createQueryBuilder('u');
        $users = $this->aclHelper->apply($qb)->getResult();
        /** @var User $user */
        foreach ($users as $user) {
            $response[] = [
                'id' => $user->getId(),
                'name' => $user->getFullName(),
            ];
        }

        return $response;
    }
}
