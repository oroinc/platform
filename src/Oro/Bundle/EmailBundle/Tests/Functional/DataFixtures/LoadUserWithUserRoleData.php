<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserWithUserRoleData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
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
     * {@inheritDoc}
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
        /** @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');
        $organization = $manager->getRepository(Organization::class)->getFirst();
        $role = $manager->getRepository(Role::class)->findOneBy(['role' => 'ROLE_USER']);

        $user = $userManager->createUser();
        $user
            ->setOwner($this->getReference('business_unit'))
            ->setUsername('limited_user')
            ->setEmail('limited_user@test.com')
            ->setPlainPassword('limited_user')
            ->addUserRole($role)
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->setFirstName('Test')
            ->setLastName('User')
            ->setEnabled(true)
            ->setSalt('');
        $apiKey = new UserApi();
        $apiKey->setApiKey('limited_user');
        $apiKey->setOrganization($organization);
        $manager->persist($apiKey);
        $user->addApiKey($apiKey);

        $userManager->updateUser($user);
        $manager->flush();

        $this->setReference($user->getUsername(), $user);
    }
}
