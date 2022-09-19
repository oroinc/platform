<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Async\Topic\SyncIntegrationTopic;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadChannelData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;
use Oro\Component\MessageQueue\Client\MessagePriority;

/**
 * @dbIsolationPerTest
 */
class IntegrationProcessTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadChannelData::class]);
        $this->getOptionalListenerManager()->enableListener('oro_workflow.listener.event_trigger_collector');
    }

    /**
     * test for schedule_integration process
     */
    public function testShouldScheduleIntegrationSyncMessageOnCreate(): void
    {
        $userManager = self::getContainer()->get('oro_user.manager');
        $admin = $userManager->findUserByEmail(LoadAdminUserData::DEFAULT_ADMIN_EMAIL);

        $integration = new Integration();

        $integration->setName('aName');
        $integration->setType('aType');
        $integration->setEnabled(true);
        $integration->setDefaultUserOwner($admin);
        $integration->setOrganization($admin->getOrganization());

        $this->getEntityManager()->persist($integration);
        $this->getEntityManager()->flush();

        self::assertMessageSent(SyncIntegrationTopic::getName(), [
            'integration_id' => $integration->getId(),
            'connector_parameters' => [],
            'connector' => null,
            'transport_batch_size' => 100,
        ]);
        self::assertMessageSentWithPriority(SyncIntegrationTopic::getName(), MessagePriority::VERY_LOW);
    }

    /**
     * test for schedule_integration process
     */
    public function testShouldNotScheduleIntegrationSyncMessageWhenChangongEnabledToFalse(): void
    {
        /** @var Integration $integration */
        $integration = $this->getReference('oro_integration:foo_integration');

        // guard
        self::assertTrue($integration->isEnabled());

        $integration->setEnabled(false);

        $this->getEntityManager()->persist($integration);
        $this->getEntityManager()->flush();

        self::assertMessagesEmpty(SyncIntegrationTopic::getName());
    }

    /**
     * test for schedule_integration process
     */
    public function testShouldNotScheduleIntegrationSyncMessageWhenChangongEnabledToTrue(): void
    {
        /** @var Integration $integration */
        $integration = $this->getReference('oro_integration:foo_integration');

        $integration->setEnabled(false);

        $this->getEntityManager()->persist($integration);
        $this->getEntityManager()->flush();

        self::getMessageCollector()->clear();

        $integration->setEnabled(true);

        $this->getEntityManager()->persist($integration);
        $this->getEntityManager()->flush();

        self::assertMessageSent(SyncIntegrationTopic::getName(), [
            'integration_id' => $integration->getId(),
            'connector_parameters' => [],
            'connector' => null,
            'transport_batch_size' => 100,
        ]);
        self::assertMessageSentWithPriority(SyncIntegrationTopic::getName(), MessagePriority::VERY_LOW);
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return self::getContainer()->get('doctrine.orm.entity_manager');
    }
}
