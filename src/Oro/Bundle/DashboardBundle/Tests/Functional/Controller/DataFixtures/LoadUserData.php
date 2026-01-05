<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    public const USER_NAME     = 'user_wo_permissions';
    public const USER_PASSWORD = 'user_api_key';

    /**
     * @var ContainerInterface
     */
    private $container;

    #[\Override]
    public function setContainer(?ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    #[\Override]
    public function getDependencies()
    {
        return [LoadOrganization::class, LoadBusinessUnit::class];
    }

    #[\Override]
    public function load(ObjectManager $manager)
    {
        /** @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');

        $role = $manager->getRepository(Role::class)
            ->findBy(array('role' => 'PUBLIC_ACCESS'));

        $user = $userManager->createUser();
        $organization = $this->getReference('organization');
        $businessUnit = $this->getReference('business_unit');

        $user
            ->setOwner($this->getReference('business_unit'))
            ->setUsername(self::USER_NAME)
            ->setPlainPassword(self::USER_PASSWORD)
            ->setFirstName('Simple')
            ->setLastName('User')
            ->addUserRole($role[0])
            ->setEmail('simple@example.com')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addBusinessUnit($businessUnit)
            ->setSalt('');

        $userManager->updateUser($user);
    }
}
