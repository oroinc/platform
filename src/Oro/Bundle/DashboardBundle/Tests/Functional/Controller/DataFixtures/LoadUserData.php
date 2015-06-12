<?php

namespace Oro\Bundle\DashboardBundle\Tests\Functional\Controller\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UserBundle\Entity\UserManager;
use Oro\Bundle\UserBundle\Entity\UserApi;

use Oro\Bundle\OrganizationBundle\Entity\Manager\OrganizationManager;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface
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

        /** @var OrganizationManager $organizationManager */
        $organizationManager = $this->container->get('oro_organization.organization_manager');
        $org = $organizationManager->getOrganizationRepo()->getFirst();

        $apiKey = new UserApi();
        $apiKey
            ->setApiKey(self::USER_PASSWORD)
            ->setUser($user)
            ->setOrganization($org);

        $user
            ->setUsername(self::USER_NAME)
            ->setPlainPassword(self::USER_PASSWORD)
            ->setFirstName('Simple')
            ->setLastName('User')
            ->addRole($role[0])
            ->setEmail('simple@example.com')
            ->addApiKey($apiKey)
            ->setOrganization($org)
            ->addOrganization($org)
            ->setSalt('');

        $userManager->updateUser($user);
    }
}
