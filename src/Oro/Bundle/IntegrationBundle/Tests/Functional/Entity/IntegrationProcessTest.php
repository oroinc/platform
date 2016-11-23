<?php
namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Async\Topics;
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

    public function setUp()
    {
        parent::setUp();

        $this->initClient();
        $this->loadFixtures([LoadChannelData::class]);
    }

    /**
     * test for schedule_integration process
     */
    public function testShouldScheduleIntegrationSyncMessageOnCreate()
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

        $traces = self::getMessageCollector()->getTopicSentMessages(Topics::SYNC_INTEGRATION);
        self::assertCount(1, $traces);
        self::assertEquals([
            'integration_id' => $integration->getId(),
            'connector_parameters' => [],
            'connector' => null,
            'transport_batch_size' => 100,
        ], $traces[0]['message']->getBody());
        self::assertEquals(MessagePriority::VERY_LOW, $traces[0]['message']->getPriority());
    }

    /**
     * test for schedule_integration process
     */
    public function testShouldNotScheduleIntegrationSyncMessageWhenChangongEnabledToFalse()
    {
        /** @var Integration $integration */
        $integration = $this->getReference('oro_integration:foo_integration');

        // guard
        self::assertTrue($integration->isEnabled());

        $integration->setEnabled(false);

        $this->getEntityManager()->persist($integration);
        $this->getEntityManager()->flush();

        $traces = self::getMessageCollector()->getTopicSentMessages(Topics::SYNC_INTEGRATION);
        self::assertCount(0, $traces);
    }


    /**
     * test for schedule_integration process
     */
    public function testShouldNotScheduleIntegrationSyncMessageWhenChangongEnabledToTrue()
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

        $traces = self::getMessageCollector()->getTopicSentMessages(Topics::SYNC_INTEGRATION);
        self::assertCount(1, $traces);
        self::assertEquals([
            'integration_id' => $integration->getId(),
            'connector_parameters' => [],
            'connector' => null,
            'transport_batch_size' => 100,
        ], $traces[0]['message']->getBody());
        self::assertEquals(MessagePriority::VERY_LOW, $traces[0]['message']->getPriority());
    }

    /**
     * @return EntityManagerInterface
     */
    private function getEntityManager()
    {
        return self::getContainer()->get('doctrine.orm.entity_manager');
    }
}
