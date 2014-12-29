<?php
namespace Oro\Bundle\TestFrameworkBundle\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\UserBundle\Entity\UserApi;
use Oro\Bundle\UserBundle\Entity\UserManager;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface, OrderedFixtureInterface
{
    /** @var ContainerInterface */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function load(ObjectManager $manager)
    {
        /** @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');

        $admin = $manager->getRepository('OroUserBundle:User')->findOneBy(array('username' => 'admin'));
        $group = $manager->getRepository('OroUserBundle:Group')->findOneBy(array('name' => 'Administrators'));
        if (!$admin) {
            $role  = $manager->getRepository('OroUserBundle:Role')->findOneBy(array('role' => 'ROLE_ADMINISTRATOR'));
            $admin = $userManager->createUser();
            $admin
                ->setUsername('admin')
                ->addRole($role);
        }

        $admin
            ->setPlainPassword('admin')
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setEmail('admin@example.com')
            ->setSalt('');

        if (!is_object($admin->getApiKeys())) {
            $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
            $api          = new UserApi();
            $api->setApiKey('admin_api_key')
                ->setUser($admin)
                ->setOrganization($organization);

            $admin->addApiKey($api);
        }

        if (!$admin->hasGroup($group)) {
            $admin->addGroup($group);
        }

        $this->addReference('default_user', $admin);

        $userManager->updateUser($admin);
    }

    public function getOrder()
    {
        return 110;
    }
}
