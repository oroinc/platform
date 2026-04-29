<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\IntegrationBundle\Model\WebhookTopic;
use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;
use Oro\Bundle\IntegrationBundle\Validator\Constraints\ValidWebhookTopic;
use Oro\Bundle\IntegrationBundle\Validator\Constraints\ValidWebhookTopicValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ValidWebhookTopicValidatorTest extends ConstraintValidatorTestCase
{
    private WebhookConfigurationProvider&MockObject $webhookConfigurationProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->webhookConfigurationProvider = $this->createMock(WebhookConfigurationProvider::class);
        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): ValidWebhookTopicValidator
    {
        return new ValidWebhookTopicValidator($this->webhookConfigurationProvider);
    }

    public function testUnexpectedConstraintThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('some.topic', $this->createMock(Constraint::class));
    }

    public function testNullValueSkipsValidation(): void
    {
        $this->webhookConfigurationProvider->expects(self::never())
            ->method('getAvailableTopics');

        $this->validator->validate(null, new ValidWebhookTopic());

        $this->assertNoViolation();
    }

    public function testEmptyStringSkipsValidation(): void
    {
        $this->webhookConfigurationProvider->expects(self::never())
            ->method('getAvailableTopics');

        $this->validator->validate('', new ValidWebhookTopic());

        $this->assertNoViolation();
    }

    public function testUnexpectedValueThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->webhookConfigurationProvider->expects(self::never())
            ->method('getAvailableTopics');

        $this->validator->validate(123, new ValidWebhookTopic());
    }

    public function testValidTopicRaisesNoViolation(): void
    {
        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getAvailableTopics')
            ->willReturn([
                'entity1.created' => new WebhookTopic('entity1.created', 'Entity1 created'),
                'entity1.updated' => new WebhookTopic('entity1.updated', 'Entity1 updated')
            ]);

        $this->validator->validate('entity1.created', new ValidWebhookTopic());

        $this->assertNoViolation();
    }

    public function testInvalidTopicRaisesViolation(): void
    {
        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getAvailableTopics')
            ->willReturn([
                'entity1.created' => new WebhookTopic('entity1.created', 'Entity1 created')
            ]);

        $constraint = new ValidWebhookTopic();
        $this->validator->validate('unknown.topic', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '"unknown.topic"')
            ->assertRaised();
    }

    public function testEmptyTopicsListRaisesViolation(): void
    {
        $this->webhookConfigurationProvider->expects(self::once())
            ->method('getAvailableTopics')
            ->willReturn([]);

        $constraint = new ValidWebhookTopic();
        $this->validator->validate('entity1.created', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '"entity1.created"')
            ->assertRaised();
    }
}
