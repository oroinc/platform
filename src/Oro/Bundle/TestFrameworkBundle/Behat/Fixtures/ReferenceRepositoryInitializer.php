<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Fixtures;

use Nelmio\Alice\Instances\Collection as AliceCollection;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\EntityBundle\ORM\Registry;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\User;

class ReferenceRepositoryInitializer
{
    /**
     * @var RegistryInterface
     */
    protected $registry;

    /**
     * @var AliceCollection
     */
    protected $referenceRepository;

    /**
     * @var ReferenceRepositoryInitializerInterface[]
     */
    protected $initializers = [];

    /**
     * @param Registry $registry
     * @param AliceCollection $referenceRepository
     */
    public function __construct(Registry $registry, AliceCollection $referenceRepository)
    {
        $this->registry = $registry;
        $this->referenceRepository = $referenceRepository;
    }

    /**
     * @param ReferenceRepositoryInitializerInterface $initializer
     */
    public function addInitializer(ReferenceRepositoryInitializerInterface $initializer)
    {
        $this->initializers[] = $initializer;
    }

    /**
     * Load references to repository
     */
    public function init()
    {
        $this->referenceRepository->clear();

        $user = $this->getDefaultUser();

        $this->referenceRepository->set('admin', $user);
        $this->referenceRepository->set('adminRole', $user->getRole(User::ROLE_ADMINISTRATOR));
        $this->referenceRepository->set('organization', $user->getOrganization());
        $this->referenceRepository->set('business_unit', $user->getOwner());

        foreach ($this->initializers as $initializer) {
            $initializer->init($this->registry, $this->referenceRepository);
        }
    }

    /**
     * References must be refreshed after each kernel shutdown
     * @throws \Doctrine\ORM\ORMException
     */
    public function refresh()
    {
        $references = $this->referenceRepository->toArray();
        $this->referenceRepository->clear();

        foreach ($references as $key => $object) {
            $class = get_class($object);
            $newReference = $this->registry->getManager()->getReference($class, $object->getId());

            $this->referenceRepository->set($key, $newReference);
        }
    }

    /**
     * Remove all references from repository
     */
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
        $repository = $this->registry->getManager()->getRepository('OroUserBundle:Role');
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
