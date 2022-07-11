<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadBusinessUnit::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $organization = $manager->getRepository(Organization::class)->getFirst();
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => 'ROLE_ADMINISTRATOR']);

        $user = $userManager->createUser();
        $user->setUsername('simple_user')
            ->setOwner($this->getReference('business_unit'))
            ->setPlainPassword('simple_password')
            ->setEmail('simple_user@example.com')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->setEnabled(true)
            ->addUserRole($role);
        $folder = new EmailFolder();
        $folder->setName('sent');
        $folder->setFullName('sent');
        $folder->setType('sent');
        $origin = new InternalEmailOrigin();
        $origin->setName('simple_user_origin_name');
        $origin->setActive(true);
        $origin->addFolder($folder);
        $origin->setOwner($user);
        $origin->setOrganization($organization);
        $user->addEmailOrigin($origin);

        $userManager->updateUser($user);

        $user2 = $userManager->createUser();
        $user2->setUsername('simple_user2')
            ->setOwner($this->getReference('business_unit'))
            ->setPlainPassword('simple_password2')
            ->setFirstName('Elley')
            ->setLastName('Towards')
            ->setEmail('simple_user2@example.com')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->setEnabled(true)
            ->addUserRole($role);
        $folder2 = new EmailFolder();
        $folder2->setName('sent');
        $folder2->setFullName('sent');
        $folder2->setType('sent');
        $origin2 = new InternalEmailOrigin();
        $origin2->setName('simple_user_origin_name_2');
        $origin2->setActive(true);
        $origin2->addFolder($folder2);
        $origin2->setOwner($user2);
        $origin2->setOrganization($organization);
        $user2->addEmailOrigin($origin);

        $userManager->updateUser($user2);

        $this->setReference($user->getUsername(), $user);
        $this->setReference($user2->getUsername(), $user2);
    }
}
