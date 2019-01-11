<?php

namespace Oro\Bundle\UserBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Nelmio\Alice\Instances\Collection as AliceCollection;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, AliceCollection $referenceRepository)
    {
        /** @var EntityManager $em */
        $em = $doctrine->getManager();
        /** @var RoleRepository $roleRepository */
        $roleRepository = $em->getRepository('OroUserBundle:Role');
        /** @var Role $role */
        $role = $roleRepository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);

        if (!$role) {
            throw new \InvalidArgumentException('Administrator role should exist.');
        }

        $user = $roleRepository->getFirstMatchedUser($role);

        if (!$user) {
            throw new \InvalidArgumentException('Administrator user should exist.');
        }

        $userRole = $roleRepository->findOneBy(['role' => User::ROLE_DEFAULT]);
        $managerRole = $roleRepository->findOneBy(['role' => LoadRolesData::ROLE_MANAGER]);
        $adminRole = $roleRepository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);

        $referenceRepository->set('admin', $user);
        $referenceRepository->set('userRole', $userRole);
        $referenceRepository->set('managerRole', $managerRole);
        $referenceRepository->set('adminRole', $adminRole);
        $referenceRepository->set('organization', $user->getOrganization());
        $referenceRepository->set('business_unit', $user->getOwner());

        $groupRepository = $em->getRepository('OroUserBundle:Group');

        $referenceRepository->set('adminsGroup', $groupRepository->findOneBy(['name' => 'Administrators']));
    }
}
