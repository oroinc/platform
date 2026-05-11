<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\IntegrationBundle\Provider\WebhookFormatProvider;
use Oro\Bundle\IntegrationBundle\Validator\Constraints\ValidWebhookFormat;
use Oro\Bundle\IntegrationBundle\Validator\Constraints\ValidWebhookFormatValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ValidWebhookFormatValidatorTest extends ConstraintValidatorTestCase
{
    private WebhookFormatProvider&MockObject $webhookFormatProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->webhookFormatProvider = $this->createMock(WebhookFormatProvider::class);
        parent::setUp();
    }

    #[\Override]
    protected function createValidator(): ValidWebhookFormatValidator
    {
        return new ValidWebhookFormatValidator($this->webhookFormatProvider);
    }

    public function testUnexpectedConstraintThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('some_format', $this->createMock(Constraint::class));
    }

    public function testNullValueSkipsValidation(): void
    {
        $this->webhookFormatProvider->expects(self::never())
            ->method('getFormats');

        $this->validator->validate(null, new ValidWebhookFormat());

        $this->assertNoViolation();
    }

    public function testEmptyStringSkipsValidation(): void
    {
        $this->webhookFormatProvider->expects(self::never())
            ->method('getFormats');

        $this->validator->validate('', new ValidWebhookFormat());

        $this->assertNoViolation();
    }

    public function testUnexpectedValueThrowsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->webhookFormatProvider->expects(self::never())
            ->method('getFormats');

        $this->validator->validate(123, new ValidWebhookFormat());
    }

    public function testValidFormatRaisesNoViolation(): void
    {
        $this->webhookFormatProvider->expects(self::once())
            ->method('getFormats')
            ->willReturn([
                'json_api' => 'JSON:API',
                'flat' => 'Flat JSON'
            ]);

        $this->validator->validate('json_api', new ValidWebhookFormat());

        $this->assertNoViolation();
    }

    public function testInvalidFormatRaisesViolation(): void
    {
        $this->webhookFormatProvider->expects(self::once())
            ->method('getFormats')
            ->willReturn([
                'json_api' => 'JSON:API'
            ]);

        $constraint = new ValidWebhookFormat();
        $this->validator->validate('unknown_format', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '"unknown_format"')
            ->assertRaised();
    }

    public function testEmptyFormatsListRaisesViolation(): void
    {
        $this->webhookFormatProvider->expects(self::once())
            ->method('getFormats')
            ->willReturn([]);

        $constraint = new ValidWebhookFormat();
        $this->validator->validate('json_api', $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ value }}', '"json_api"')
            ->assertRaised();
    }
}
