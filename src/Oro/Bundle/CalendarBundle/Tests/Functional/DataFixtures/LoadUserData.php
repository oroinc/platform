<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\UserBundle\Entity\UserApi;

class LoadUserData extends AbstractFixture implements DependentFixtureInterface, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData'
        ];
    }

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
        $organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
        $userManager = $this->container->get('oro_user.manager');
        foreach (['simple_user_1', 'simple_user_2', 'simple_user_3'] as $username) {
            $user = $userManager->createUser();
            $user->setUsername($username)
                ->setPlainPassword($username)
                ->setEmail(sprintf('%s@example.com', $username))
                ->setFirstName($username)
                ->setLastName($username)
                ->setOrganization($organization)
                ->addOrganization($organization)
                ->setEnabled(true);
            $userManager->updateUser($user);
            $this->setReference($user->getUsername(), $user);
        }

        $user = $userManager->createUser();
        $role = $manager->getRepository('OroUserBundle:Role')->findOneBy(['role' => 'ROLE_ADMINISTRATOR']);
        $apiKey = new UserApi();
        $apiKey->setApiKey('foo_user_1_api_key');
        $apiKey->setOrganization($organization);
        $user->setUsername('foo_user_1')
            ->setPlainPassword('password')
            ->setEmail('foo_user_1@example.com')
            ->setFirstName('Billy')
            ->setLastName('Wilf')
            ->addApiKey($apiKey)
            ->setOrganization($organization)
            ->addOrganization($organization)
            ->addRole($role)
            ->setEnabled(true);
        $userManager->updateUser($user);
        $this->setReference('oro_calendar:user:foo_user_1', $user);

        $calendar = $manager->getRepository('OroCalendarBundle:Calendar')
            ->findOneBy(
                [
                    'owner' => $user,
                    'organization' => $user->getOrganization(),
                ]
            );

        $this->setReference('oro_calendar:calendar:foo_user_1', $calendar);
    }
}
