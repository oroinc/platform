<?php

declare(strict_types=1);

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Provider;

use Oro\Bundle\AttachmentBundle\Provider\MetadataServiceProvider;
use Oro\Bundle\AttachmentBundle\Provider\MetadataServiceRequirementsProvider;

class MetadataServiceRequirementsProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetRecommendationsWhenServiceNotEnabled(): void
    {
        $metadataServiceProvider = $this->createMock(MetadataServiceProvider::class);
        $metadataServiceProvider->expects(self::never())
            ->method('isServiceHealthy');

        $provider = new MetadataServiceRequirementsProvider(false, $metadataServiceProvider);

        $collection = $provider->getRecommendations();
        $recommendations = $collection->all();

        self::assertCount(1, $recommendations);
        self::assertFalse($recommendations[0]->isFulfilled());
        self::assertStringContainsString(
            'Metadata Service URL and API Key are configured',
            $recommendations[0]->getTestMessage()
        );
    }

    public function testGetRecommendationsWhenServiceEnabledAndHealthy(): void
    {
        $metadataServiceProvider = $this->createMock(MetadataServiceProvider::class);
        $metadataServiceProvider->expects(self::once())
            ->method('isServiceHealthy')
            ->willReturn(true);

        $provider = new MetadataServiceRequirementsProvider(true, $metadataServiceProvider);

        $collection = $provider->getRecommendations();
        $recommendations = $collection->getRecommendations();
        $requirements = $collection->getRequirements();

        self::assertCount(1, $recommendations);
        self::assertCount(1, $requirements);

        self::assertTrue($recommendations[0]->isFulfilled());
        self::assertStringContainsString(
            'Metadata Service URL and API Key are configured',
            $recommendations[0]->getTestMessage()
        );

        self::assertTrue($requirements[0]->isFulfilled());
        self::assertStringContainsString('Metadata Service is accessible', $requirements[0]->getTestMessage());
    }

    public function testGetRecommendationsWhenServiceEnabledButNotHealthy(): void
    {
        $metadataServiceProvider = $this->createMock(MetadataServiceProvider::class);
        $metadataServiceProvider->expects(self::once())
            ->method('isServiceHealthy')
            ->willReturn(false);

        $provider = new MetadataServiceRequirementsProvider(true, $metadataServiceProvider);

        $collection = $provider->getRecommendations();
        $recommendations = $collection->getRecommendations();
        $requirements = $collection->getRequirements();

        self::assertCount(1, $recommendations);
        self::assertCount(1, $requirements);

        self::assertTrue($recommendations[0]->isFulfilled());
        self::assertStringContainsString(
            'Metadata Service URL and API Key are configured',
            $recommendations[0]->getTestMessage()
        );

        self::assertFalse($requirements[0]->isFulfilled());
        self::assertStringContainsString('Metadata Service is accessible', $requirements[0]->getTestMessage());
    }
}
