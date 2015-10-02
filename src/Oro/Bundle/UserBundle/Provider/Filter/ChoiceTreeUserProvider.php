<?php

namespace Oro\Bundle\UserBundle\Provider\Filter;

use Oro\Bundle\UserBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class ChoiceTreeUserProvider
{
    /**
     * @var Registry
     */
    protected $registry;

    /** @var AclHelper */
    protected $aclHelper;

    public function __construct(Registry $registry, AclHelper $aclHelper)
    {
        $this->registry = $registry;
        $this->aclHelper = $aclHelper;
    }

    public function getList()
    {
        $response = [];
        $qb = $this->registry->getManager()->getRepository('OroUserBundle:User')->createQueryBuilder('u');
        $users = $this->aclHelper->apply($qb)->getResult();

        foreach ($users as $user) {
            $response[] = [
                'id' => $user->getId(),
                'name' => $user->getFullName(),
            ];
        }

        return $response;
    }
}
