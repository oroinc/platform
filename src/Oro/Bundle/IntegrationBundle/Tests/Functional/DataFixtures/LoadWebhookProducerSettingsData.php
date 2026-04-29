<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;

class LoadWebhookProducerSettingsData extends AbstractFixture implements DependentFixtureInterface
{
    private array $data = [
        [
            'notificationUrl' => 'https://example.com/webhook/department.created/1',
            'topic' => 'testdepartment.created',
            'secret' => 'secret1',
            'enabled' => true,
            'verifySsl' => true,
            'reference' => 'oro_integration:webhook_department.create_enabled'
        ],
        [
            'notificationUrl' => 'https://example.com/webhook/department.created/2',
            'topic' => 'testdepartment.created',
            'secret' => 'secret2',
            'enabled' => true,
            'verifySsl' => true,
            'reference' => 'oro_integration:webhook_department.create_enabled_second'
        ],
        [
            'notificationUrl' => 'https://example.com/webhook/department.updated',
            'topic' => 'testdepartment.updated',
            'secret' => 'secret3',
            'enabled' => true,
            'verifySsl' => true,
            'reference' => 'oro_integration:webhook_department.update_enabled'
        ],
        [
            'notificationUrl' => 'https://example.com/webhook/department.created/disabled',
            'topic' => 'testdepartment.created',
            'secret' => 'secret4',
            'enabled' => false,
            'verifySsl' => true,
            'reference' => 'oro_integration:webhook_department.create_disabled'
        ],
        [
            'notificationUrl' => 'https://example.com/webhook/employee.created',
            'topic' => 'testemployee.created',
            'secret' => 'secret5',
            'enabled' => true,
            'verifySsl' => true,
            'reference' => 'oro_integration:webhook_employee.create_enabled'
        ],
        [
            'notificationUrl' => 'https://example.com/webhook/employee.deleted',
            'topic' => 'testemployee.deleted',
            'secret' => null,
            'enabled' => false,
            'verifySsl' => false,
            'reference' => 'oro_integration:webhook_employee.delete_disabled'
        ],
        [
            'notificationUrl' => 'https://example.com/webhook/department.created/system',
            'topic' => 'testdepartment.created',
            'secret' => null,
            'enabled' => true,
            'verifySsl' => false,
            'system' => true,
            'reference' => 'oro_integration:webhook_department.create_system'
        ]
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadUser::class,
            LoadOrganization::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        $organization = $user->getOrganization();

        foreach ($this->data as $data) {
            $webhook = new WebhookProducerSettings();
            $webhook->setNotificationUrl($data['notificationUrl']);
            $webhook->setTopic($data['topic']);
            $webhook->setSecret($data['secret']);
            $webhook->setEnabled($data['enabled']);
            $webhook->setVerifySsl($data['verifySsl']);
            $webhook->setOwner($user);
            $webhook->setOrganization($organization);
            $webhook->setFormat('default');
            if (!empty($data['system'])) {
                $webhook->setSystem(true);
            }

            $manager->persist($webhook);
            $this->setReference($data['reference'], $webhook);
        }

        $manager->flush();
    }
}
