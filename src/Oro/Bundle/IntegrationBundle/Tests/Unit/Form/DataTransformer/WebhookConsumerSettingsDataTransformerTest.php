<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\IntegrationBundle\Entity\WebhookConsumerSettings;
use Oro\Bundle\IntegrationBundle\Form\DataTransformer\WebhookConsumerSettingsDataTransformer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WebhookConsumerSettingsDataTransformerTest extends TestCase
{
    private ManagerRegistry&MockObject $registry;
    private ObjectRepository&MockObject $repository;
    private WebhookConsumerSettingsDataTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(ObjectRepository::class);

        $this->transformer = new WebhookConsumerSettingsDataTransformer($this->registry, 'test_processor');
    }

    public function testTransformWithWebhookConsumerSettingsEntity(): void
    {
        $webhook = new WebhookConsumerSettings();
        $webhook->setId('test-uuid-123');

        $result = $this->transformer->transform($webhook);

        self::assertSame('test-uuid-123', $result);
    }

    public function testTransformWithNullValue(): void
    {
        $result = $this->transformer->transform(null);

        self::assertIsString($result);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $result
        );
    }

    public function testTransformWithNonWebhookConsumerSettingsValue(): void
    {
        $result = $this->transformer->transform('some-value');

        self::assertIsString($result);
        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $result
        );
    }

    public function testReverseTransformWithNullValue(): void
    {
        $result = $this->transformer->reverseTransform(null);

        self::assertNull($result);
    }

    public function testReverseTransformWithEmptyString(): void
    {
        $result = $this->transformer->reverseTransform('');

        self::assertNull($result);
    }

    public function testReverseTransformWithExistingWebhook(): void
    {
        $existingWebhook = new WebhookConsumerSettings();
        $existingWebhook->setId('existing-uuid');
        $existingWebhook->setProcessor('existing_processor');

        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(WebhookConsumerSettings::class)
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
            ->method('find')
            ->with('existing-uuid')
            ->willReturn($existingWebhook);

        $result = $this->transformer->reverseTransform('existing-uuid');

        self::assertSame($existingWebhook, $result);
        self::assertSame('existing-uuid', $result->getId());
        self::assertSame('existing_processor', $result->getProcessor());
    }

    public function testReverseTransformWithNonExistingWebhook(): void
    {
        $this->registry->expects(self::once())
            ->method('getRepository')
            ->with(WebhookConsumerSettings::class)
            ->willReturn($this->repository);

        $this->repository->expects(self::once())
            ->method('find')
            ->with('new-uuid')
            ->willReturn(null);

        $result = $this->transformer->reverseTransform('new-uuid');

        self::assertInstanceOf(WebhookConsumerSettings::class, $result);
        self::assertSame('new-uuid', $result->getId());
        self::assertSame('test_processor', $result->getProcessor());
    }
}
