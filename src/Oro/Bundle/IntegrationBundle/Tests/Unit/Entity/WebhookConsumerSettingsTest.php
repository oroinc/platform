<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Entity;

use Oro\Bundle\IntegrationBundle\Entity\WebhookConsumerSettings;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class WebhookConsumerSettingsTest extends TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors(): void
    {
        $properties = [
            ['id', 'test-uuid-123'],
            ['processor', 'test_processor'],
            ['enabled', true, false],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];

        self::assertPropertyAccessors(new WebhookConsumerSettings(), $properties);
    }

    public function testIsEnabledDefaultValue(): void
    {
        $entity = new WebhookConsumerSettings();

        self::assertTrue($entity->isEnabled());
    }
}
