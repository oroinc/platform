<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Doctrine\ORM\EntityManager;
use Nelmio\Alice\Instances\Collection;
use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\User;

class ReferenceRepositoryInitializer
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var Collection
     */
    protected $referenceRepository = [];

    /**
     * @param Registry $registry
     * @param Collection $referenceRepository
     */
    public function __construct(Registry $registry, Collection $referenceRepository)
    {
        $this->em = $registry->getManager();
        $this->referenceRepository = $referenceRepository;
    }

    /**
     * Load references to repository
     */
    public function init()
    {
        $this->referenceRepository->clear();

        $user = $this->getDefaultUser();

        $this->referenceRepository->set('admin', $user);
        $this->referenceRepository->set('organization', $user->getOrganization());
        $this->referenceRepository->set('business_unit', $user->getOwner());
    }

    public function refresh()
    {
        $references = $this->referenceRepository->toArray();
        $this->referenceRepository->clear();

        foreach ($references as $key => $object) {
            $class = get_class($object);
            $newReference = $this->em->getReference($class, $object->getId());

            $this->referenceRepository->set($key, $newReference);
        }
    }

    public function clear()
    {
        $this->referenceRepository->clear();
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
                'Administrator user should exist.'
            );
        }

        return $user;
    }
}
