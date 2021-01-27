<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\TestFrameworkBundle\Entity\TestIntegrationTransport;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
            'connectors' => ['connector1'],
            'reference' => 'oro_integration:foo_integration'
        ],
        [
            'name' => 'Bar Integration',
            'type' => 'bar',
            'enabled' => true,
            'connectors' => ['connector1'],
            'reference' => 'oro_integration:bar_integration'
        ],
        [
            'name' => 'Extended Bar Integration',
            'type' => 'bar',
            'enabled' => true,
            'connectors' => ['connector1'],
            'reference' => 'oro_integration:extended_bar_integration'
        ],
        [
            'name' => 'No connectors Integration',
            'type' => 'no_connectors',
            'enabled' => true,
            'connectors' => [],
            'reference' => 'oro_integration:extended_bar_integration'
        ],
        [
            'name' => 'Disabled Integration',
            'type' => 'disabled',
            'enabled' => false,
            'connectors' => ['connector1'],
            'reference' => 'oro_integration:extended_bar_integration'
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
            $transport = new TestIntegrationTransport();
            $manager->persist($transport);

            $integration = new Integration();

            $integration->setName($data['name']);
            $integration->setType($data['type']);
            $integration->setEnabled($data['enabled']);
            $integration->setConnectors($data['connectors']);
            $integration->setDefaultUserOwner($admin);
            $integration->setOrganization($admin->getOrganization());
            $integration->setTransport($transport);

            $this->setReference($data['reference'], $integration);

            $manager->persist($integration);
        }

        $manager->flush();
    }
}
