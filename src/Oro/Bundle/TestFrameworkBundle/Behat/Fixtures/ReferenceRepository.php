<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\User;

class ReferenceRepository
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var array
     */
    public $references = [];

    /**
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->em = $registry->getManager();
    }

    public function init()
    {
        $user = $this->getDefaultUser();

        $this->references['admin'] = $user;
        $this->references['organization'] = $user->getOrganization();
        $this->references['unit'] = $user->getOwner();
    }

    public function clear()
    {
        $this->references = [];
    }

    /**
     * @return User
     * @throws \InvalidArgumentException
     */
    protected function getDefaultUser()
    {
        /** @var RoleRepository $repository */
        $repository = $this->em->getRepository('OroUserBundle:Role');
        $role       = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);

        if (!$role) {
            throw new \InvalidArgumentException('Administrator role should exist.');
        }

        $user = $repository->getFirstMatchedUser($role);

        if (!$user) {
            throw new \InvalidArgumentException(
                'Administrator user should exist to load dashboard configuration.'
            );
        }

        return $user;
    }
}
