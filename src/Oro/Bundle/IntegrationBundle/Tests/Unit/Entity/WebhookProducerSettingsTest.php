<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class WebhookProducerSettingsTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        $properties = [
            ['id', 'test-uuid-123'],
            ['notificationUrl', 'https://example.com/webhook'],
            ['topic', 'order.created'],
            ['secret', 'test_secret'],
            ['enabled', true, false],
            ['verifySsl', true, false],
            ['format', 'test'],
            ['system', true, false],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
            ['owner', new User()],
            ['organization', new Organization()],
        ];

        self::assertPropertyAccessors(new WebhookProducerSettings(), $properties);
    }

    public function testIsEnabledDefaultValue(): void
    {
        $entity = new WebhookProducerSettings();

        self::assertTrue($entity->isEnabled());
    }

    public function testIsVerifySslDefaultValue(): void
    {
        $entity = new WebhookProducerSettings();

        self::assertTrue($entity->isVerifySsl());
    }

    public function testIsSystemDefaultValue(): void
    {
        $entity = new WebhookProducerSettings();

        self::assertFalse($entity->isSystem());
    }
}
