<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;

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
        $userManager = $this->container->get('oro_user.manager');
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $role = $manager->getRepository('OroUserBundle:Role')->findOneBy(['role' => 'ROLE_ADMINISTRATOR']);

        $user = $userManager->createUser();
        $user->setUsername('simple_user')
            ->setPlainPassword('simple_password')
            ->setEmail('simple_user@example.com')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->setEnabled(true)
            ->addRole($role);
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
            ->setPlainPassword('simple_password2')
            ->setFirstName('Elley')
            ->setLastName('Towards')
            ->setEmail('simple_user2@example.com')
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->setEnabled(true);
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
