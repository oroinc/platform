<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\API\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UserBundle\Entity\UserApi;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface
{
    const USER_NAME = 'user_wo_permissions';
    const USER_PASSWORD = 'user_api_key1Q';

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        /** @var \Oro\Bundle\UserBundle\Entity\UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');
        $role = $userManager->getStorageManager()
            ->getRepository('OroUserBundle:Role')
            ->findOneBy(array('role' => 'IS_AUTHENTICATED_ANONYMOUSLY'));

        $group = $userManager->getStorageManager()
            ->getRepository('OroUserBundle:Group')
            ->findOneBy(array('name' => 'Administrators'));

        $user = $userManager->createUser();

        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        $api = new UserApi();
        $api->setApiKey('user_api_key')
            ->setOrganization($organization)
            ->setUser($user);

        $user
            ->setUsername(self::USER_NAME)
            ->setPlainPassword(self::USER_PASSWORD)
            ->setFirstName('Simple')
            ->setLastName('User')
            ->addRole($role)
            ->addGroup($group)
            ->setEmail('simple@example.com')
            ->setOrganization($organization)
            ->setOrganizations(new ArrayCollection([$organization]))
            ->addApiKey($api)
            ->setSalt('');

        $userManager->updateUser($user);
    }
}
