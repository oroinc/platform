<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadUsersWithSameEmailInLowercase extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    const EMAIL = 'duplicated_email@example.com';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $organization = $manager->getRepository(Organization::class)->getFirst();
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => User::ROLE_DEFAULT]);

        $user = $userManager->createUser();
        $user->setUsername('duplicated_email1')
            ->setPlainPassword('password1')
            ->setEmail(self::EMAIL)
            ->setFirstName('Elley')
            ->setLastName('Towards')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addUserRole($role)
            ->setEnabled(true);
        $userManager->updateUser($user);

        $user2 = $userManager->createUser();
        $user2->setUsername('duplicated_email2')
            ->setPlainPassword('password2')
            ->setEmail(self::EMAIL . 2)
            ->setFirstName('Merry')
            ->setLastName('Backwards')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addUserRole($role)
            ->setEnabled(true);
        $userManager->updateUser($user2);

        /** @var EntityManager $manager */
        $manager->getConnection()
            ->executeQuery(
                'UPDATE oro_user SET email_lowercase = ? WHERE id = ?',
                [self::EMAIL, $user2->getId()]
            );
    }
}
