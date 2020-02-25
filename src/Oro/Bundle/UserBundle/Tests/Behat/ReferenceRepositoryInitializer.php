<?php

namespace Oro\Bundle\UserBundle\Tests\Behat;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\ReferenceRepositoryInitializerInterface;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\Collection;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\UserBundle\Entity\Repository\RoleRepository;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadRolesData;

class ReferenceRepositoryInitializer implements ReferenceRepositoryInitializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function init(Registry $doctrine, Collection $referenceRepository)
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

        $referenceRepository->set(
            'oro_group_all_grid_view_label',
            $doctrine->getManager()->getRepository(TranslationKey::class)->findOneBy([
                'key' => 'oro.user.group.entity_grid_all_view_label',
                'domain' => 'messages'
            ])
        );
    }
}
