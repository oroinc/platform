<?php

namespace Oro\Bundle\IntegrationBundle\Validator\Constraints;

use Oro\Bundle\IntegrationBundle\Provider\WebhookFormatProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the format value is one of the available formats
 * provided by {@see WebhookFormatProvider::getFormats()}.
 */
class ValidWebhookFormatValidator extends ConstraintValidator
{
    public function __construct(
        private readonly WebhookFormatProvider $webhookFormatProvider
    ) {
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidWebhookFormat) {
            throw new UnexpectedTypeException($constraint, ValidWebhookFormat::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $availableFormats = $this->webhookFormatProvider->getFormats();
        if (!isset($availableFormats[$value])) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
        }
    }
}
