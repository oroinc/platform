<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadNotificationAlertData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    protected ContainerInterface $container;

    protected array $data = [
        [
            'id'            => 'c8e80ed1-4615-431e-995a-54947fc2f999',
            'source_type'   => 'MS365',
            'alert_type'    => 'auth',
            'resource_type' => 'tasks',
            'created_at'    => '2021-07-15 17:04:07',
            'operation'     => 'import',
            'step'          => 'get',
            'item_id'       => 1,
            'external_id'   => 'c8e80ed1-4615-431e-995a-9',
            'reference'     => 'notification_alert_1'
        ],
        [
            'id'            => 'c8e80ed1-4615-431e-995a-54947fc2f990',
            'source_type'   => 'MS365',
            'alert_type'    => 'sync',
            'resource_type' => 'tasks',
            'created_at'    => '2021-07-15 17:04:07',
            'operation'     => 'import',
            'step'          => 'map',
            'item_id'       => 2,
            'external_id'   => 'c8e80ed1-4615-431e-995a-0',
            'reference'     => 'notification_alert_2'
        ],
        [
            'id'            => 'c8e80ed1-4615-431e-995a-54947fc2f991',
            'source_type'   => 'MS365',
            'alert_type'    => 'auth',
            'resource_type' => 'calendar event',
            'created_at'    => '2021-07-15 18:04:07',
            'operation'     => 'import',
            'step'          => 'get',
            'item_id'       => 3,
            'external_id'   => 'c8e80ed1-4615-431e-995a-1',
            'reference'     => 'notification_alert_3'
        ],
        [
            'id'            => 'c8e80ed1-4615-431e-995a-54947fc2f992',
            'source_type'   => 'MS365',
            'alert_type'    => 'sync',
            'resource_type' => 'calendar event',
            'created_at'    => '2021-07-15 19:04:07',
            'operation'     => 'import',
            'step'          => 'map',
            'item_id'       => 4,
            'external_id'   => 'c8e80ed1-4615-431e-995a-2',
            'reference'     => 'notification_alert_4'
        ]
    ];

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function getDependencies(): array
    {
        return [
            LoadUserData::class
        ];
    }

    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        /** @var OrganizationInterface $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        foreach ($this->data as $data) {
            $alert = new NotificationAlert();
            $alert->setId($data['id']);
            $alert->setSourceType($data['source_type']);
            $alert->setAlertType($data['alert_type']);
            $alert->setResourceType($data['resource_type']);
            $alert->setCreatedAt(new \DateTime($data['created_at'], new \DateTimeZone('UTC')));
            $alert->setUpdatedAt(new \DateTime($data['created_at'], new \DateTimeZone('UTC')));
            $alert->setOperation($data['operation']);
            $alert->setStep($data['step']);
            $alert->setItemId($data['item_id']);
            $alert->setExternalId($data['external_id']);
            $alert->setUser($user);
            $alert->setOrganization($organization);

            $manager->persist($alert);
        }

        $manager->flush();
    }
}
