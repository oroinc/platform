<?php

namespace Oro\Bundle\UserBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCommandUserCreateUpdateData extends AbstractFixture implements ContainerAwareInterface
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
        $businessUnitName = 'bu1';
        $organizationsCount = 3;

        /** @var Organization[] $organizations */
        $organizations = [];

        for ($i = 1; $i <= $organizationsCount; $i++) {
            $key = 'org' . $i;
            $organizations[$key] = new Organization();
            $organizations[$key]
                ->setName($key)
                ->setEnabled(true)
            ;
            $manager->persist($organizations[$key]);
            $this->addReference($key, $organizations[$key]);
        }

        $businessUnit = new BusinessUnit();
        $businessUnit
            ->setName($businessUnitName)
            ->setOrganization($organizations['org1'])
        ;
        $manager->persist($businessUnit);
        $this->addReference('bu1', $businessUnit);

        $userManager = $this->container->get('oro_user.manager');

        /** @var User $user */
        $user = $userManager->createUser();
        $user
            ->setUsername('test_user_main')
            ->setPlainPassword('admin1Q')
            ->setEmail('test_user_main@example.com')
            ->setEnabled(true)
        ;
        $userManager->updateUser($user, false);
        $this->setReference($user->getUsername(), $user);
        $manager->flush();
    }
}
