<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\TestFrameworkBundle\Entity\TestIntegrationTransport;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

class LoadChannelData extends AbstractFixture implements DependentFixtureInterface
{
    private array $data = [
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
            'reference' => 'oro_integration:no_connectors_integration'
        ],
        [
            'name' => 'Disabled Integration',
            'type' => 'disabled',
            'enabled' => false,
            'connectors' => ['connector1'],
            'reference' => 'oro_integration:disabled_integration'
        ]
    ];

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadUser::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        foreach ($this->data as $data) {
            $transport = new TestIntegrationTransport();
            $manager->persist($transport);

            $integration = new Integration();
            $integration->setName($data['name']);
            $integration->setType($data['type']);
            $integration->setEnabled($data['enabled']);
            $integration->setConnectors($data['connectors']);
            $integration->setDefaultUserOwner($user);
            $integration->setOrganization($user->getOrganization());
            $integration->setTransport($transport);
            $manager->persist($integration);

            $this->setReference($data['reference'], $integration);
        }

        $manager->flush();
    }
}
