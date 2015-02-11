<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

class LoadChannelData extends AbstractFixture implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $data = [
        [
            'name' => 'Foo Integration',
            'type' => 'foo',
            'enabled' => true,
            'reference' => 'oro_integration:foo_integration'
        ],
        [
            'name' => 'Bar Integration',
            'type' => 'bar',
            'enabled' => true,
            'reference' => 'oro_integration:bar_integration'
        ]
    ];

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
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);

        foreach ($this->data as $data) {
            $integration = new Integration();

            $integration->setName($data['name']);
            $integration->setType($data['type']);
            $integration->setEnabled($data['enabled']);
            $integration->setDefaultUserOwner($admin);
            $integration->setOrganization($admin->getOrganization());

            $this->setReference($data['reference'], $integration);

            $manager->persist($integration);
        }

        $manager->flush();
    }
}
