<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\IntegrationBundle\Entity\Repository\WebhookProducerSettingsRepository;
use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\IntegrationBundle\Tests\Functional\DataFixtures\LoadWebhookProducerSettingsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class WebhookProducerSettingsRepositoryTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadWebhookProducerSettingsData::class]);
    }

    private function getRepository(): WebhookProducerSettingsRepository
    {
        return self::getContainer()->get('doctrine')->getRepository(WebhookProducerSettings::class);
    }

    public function testHasActiveWebhooksReturnsTrueWhenActiveWebhookExists(): void
    {
        $result = $this->getRepository()->hasActiveWebhooks('testdepartment.created');

        $this->assertTrue($result);
    }

    public function testHasActiveWebhooksReturnsFalseWhenNoActiveWebhookExists(): void
    {
        $result = $this->getRepository()->hasActiveWebhooks('testemployee.deleted');

        $this->assertFalse($result);
    }

    public function testHasActiveWebhooksReturnsFalseForNonExistentTopic(): void
    {
        $result = $this->getRepository()->hasActiveWebhooks('non_existent.topic');

        $this->assertFalse($result);
    }

    public function testGetActiveWebhooksReturnsMultipleWebhooksForTopic(): void
    {
        $webhooks = $this->getRepository()->getActiveWebhooks('testdepartment.created');

        $this->assertCount(3, $webhooks);
        $this->assertContainsOnlyInstancesOf(WebhookProducerSettings::class, $webhooks);

        $expectedIds = [
            $this->getReference('oro_integration:webhook_department.create_enabled')->getId(),
            $this->getReference('oro_integration:webhook_department.create_enabled_second')->getId(),
            $this->getReference('oro_integration:webhook_department.create_system')->getId()
        ];

        $actualIds = array_map(fn (WebhookProducerSettings $webhook) => $webhook->getId(), $webhooks);

        $this->assertEqualsCanonicalizing($expectedIds, $actualIds);
    }

    public function testGetActiveWebhooksReturnsSingleWebhookWhenOnlyOneActive(): void
    {
        $webhooks = $this->getRepository()->getActiveWebhooks('testdepartment.updated');

        $this->assertCount(1, $webhooks);
        $this->assertEquals(
            $this->getReference('oro_integration:webhook_department.update_enabled')->getId(),
            $webhooks[0]->getId()
        );
    }

    public function testGetActiveWebhooksReturnsEmptyArrayWhenNoActiveWebhooks(): void
    {
        $webhooks = $this->getRepository()->getActiveWebhooks('testemployee.deleted');

        $this->assertIsArray($webhooks);
        $this->assertEmpty($webhooks);
    }

    public function testGetActiveWebhooksReturnsEmptyArrayForNonExistentTopic(): void
    {
        $webhooks = $this->getRepository()->getActiveWebhooks('non_existent.topic');

        $this->assertIsArray($webhooks);
        $this->assertEmpty($webhooks);
    }

    public function testGetActiveWebhooksExcludesDisabledWebhooks(): void
    {
        $webhooks = $this->getRepository()->getActiveWebhooks('testdepartment.created');

        $this->assertCount(3, $webhooks);

        $disabledWebhookId = $this->getReference('oro_integration:webhook_department.create_disabled')->getId();
        foreach ($webhooks as $webhook) {
            $this->assertNotEquals($disabledWebhookId, $webhook->getId());
        }
    }

    public function testGetActiveWebhooksForDifferentTopics(): void
    {
        $departmentWebhooks = $this->getRepository()->getActiveWebhooks('testdepartment.created');
        $employeeWebhooks = $this->getRepository()->getActiveWebhooks('testemployee.created');

        $this->assertCount(3, $departmentWebhooks);
        $this->assertCount(1, $employeeWebhooks);

        $this->assertEquals(
            $this->getReference('oro_integration:webhook_employee.create_enabled')->getId(),
            $employeeWebhooks[0]->getId()
        );
    }
}
