<?php

namespace Oro\Bundle\UserBundle\Entity\Manager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Status;
use Oro\Bundle\UserBundle\Entity\UserManager;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\UserBundle\Migrations\Schema\v1_10\OroUserBundle;

class ManagerUser
{
    /**
     * @var Registry
     */
    protected $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function getList()
    {
        $response = [];
        $users = $this->registry->getManager()->getRepository('OroUserBundle:User')->findAll();

        foreach ($users as $user) {
            $response[] = [
                'id' => $user->getId(),
                'name' => $user->getFullName(),
            ];
        }

        return $response;
    }
}
