<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadTranslationUsers extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    const TRANSLATOR_USERNAME = 'translator';
    const TRANSLATOR_EMAIL = 'translator@example.com';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadTranslationRoles::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->loadTranslator($manager, $this->container->get('oro_user.manager'));
    }

    /**
     * @param ObjectManager $manager
     * @param UserManager $userManager
     */
    public function loadTranslator(ObjectManager $manager, UserManager $userManager)
    {
        $role = $manager->getRepository('OroUserBundle:Role')
            ->findOneBy(['role' => LoadTranslationRoles::ROLE_TRANSLATOR]);

        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->findOneBy([]);

        /* @var $user User */
        $user = $userManager->createUser();
        $user
            ->setFirstName('Demo')
            ->setLastName('Translator')
            ->setEmail(self::TRANSLATOR_EMAIL)
            ->setPlainPassword(self::TRANSLATOR_USERNAME)
            ->addRole($role)
            ->setEnabled(true)
            ->setUsername(self::TRANSLATOR_USERNAME)
            ->setOrganization($organization)
            ->addOrganization($organization);

        $userManager->updateUser($user);
    }
}
