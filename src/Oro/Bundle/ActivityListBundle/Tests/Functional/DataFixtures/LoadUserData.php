<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;

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

    protected function initUser (ObjectManager $manager)
    {
        $userManager = $this->container->get('oro_user.manager');
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $role = $manager->getRepository('OroUserBundle:Role')->findOneBy(array('role' => 'ROLE_MANAGER'));

        $user = $userManager->createUser();
        $user->setUsername('manager_user')
            ->setPlainPassword('simple_password')
            ->setEmail('simple_user@example.com')
            ->setOrganization($organization)
            ->setEnabled(true)
            ->addRole($role);

        $folder = new EmailFolder();
        $folder->setName('sent');
        $folder->setFullName('sent');
        $folder->setType('sent');
        $origin = new InternalEmailOrigin();
        $origin->setName('simple_user_origin_name');
        $origin->setIsActive(true);
        $origin->addFolder($folder);
        $origin->setOwner($user);
        $origin->setOrganization($organization);
        $user->addEmailOrigin($origin);

        $userManager->updateUser($user);

        $this->setReference($user->getUsername(), $user);
    }
}
