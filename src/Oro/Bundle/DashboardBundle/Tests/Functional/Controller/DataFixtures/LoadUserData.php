<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    const USER_NAME     = 'user_wo_permissions';
    const USER_PASSWORD = 'user_api_key';

    /**
     * @var ContainerInterface
     */
    private $container;

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
        return [LoadOrganization::class];
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');

        $role = $userManager
            ->getStorageManager()
            ->getRepository('OroUserBundle:Role')
            ->findBy(array('role' => 'IS_AUTHENTICATED_ANONYMOUSLY'));

        $user = $userManager->createUser();
        $organization = $this->getReference('organization');

        $apiKey = new UserApi();
        $apiKey
            ->setApiKey('user_api_key')
            ->setUser($user)
            ->setOrganization($organization);

        $user
            ->setUsername(self::USER_NAME)
            ->setPlainPassword(self::USER_PASSWORD)
            ->setFirstName('Simple')
            ->setLastName('User')
            ->addRole($role[0])
            ->setEmail('simple@example.com')
            ->addApiKey($apiKey)
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->setSalt('');

        $userManager->updateUser($user);
    }
}
