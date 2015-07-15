<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

use Oro\Bundle\UserBundle\Entity\UserApi;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;

class LoadUserData extends AbstractFixture implements ContainerAwareInterface
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
    public function load(ObjectManager $manager)
    {
        $this->initUser($manager);
        $manager->flush();
    }

    protected function initUser(ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $roleManager = $manager->getRepository('OroUserBundle:Role')->findOneBy(['role' => 'ROLE_USER']);
        $roleAdmin = $manager->getRepository('OroUserBundle:Role')->findOneBy(['role' => 'ROLE_ADMINISTRATOR']);
        $group = $manager->getRepository('OroUserBundle:Group')->findOneBy(array('name' => 'Administrators'));

        $user = $userManager->createUser();
        $user->setUsername('manager_user')
            ->setPlainPassword('simple_password')
            ->setEmail('simple_user@example.com')
            ->setOrganization($organization)
            ->setEnabled(true)
            ->setFirstname('Test')
            ->setLastname('Test')
            ->setSalt('')
            ->removeRole($roleAdmin);
        $user->addRole($roleManager);
        if (0 === count($user->getApiKeys())) {
            $api = new UserApi();
            $api->setApiKey('manager_api_key')
                ->setUser($user)
                ->setOrganization($organization);
            $user->addApiKey($api);
        }
        if (!$user->hasGroup($group)) {
            $user->addGroup($group);
        }
        $userManager->updateUser($user);
        $this->setReference($user->getUsername(), $user);
    }
}
