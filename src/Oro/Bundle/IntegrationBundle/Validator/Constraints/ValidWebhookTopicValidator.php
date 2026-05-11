<?php

namespace Oro\Bundle\IntegrationBundle\Validator\Constraints;

use Oro\Bundle\IntegrationBundle\Provider\WebhookConfigurationProvider;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validates that the topic value is one of the available webhook topics
 * provided by {@see WebhookConfigurationProvider::getAvailableTopics()}.
 */
class ValidWebhookTopicValidator extends ConstraintValidator
{
    public function __construct(
        private readonly WebhookConfigurationProvider $webhookConfigurationProvider
    ) {
    }

    #[\Override]
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidWebhookTopic) {
            throw new UnexpectedTypeException($constraint, ValidWebhookTopic::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value)) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $availableTopics = $this->webhookConfigurationProvider->getAvailableTopics();
        if (!isset($availableTopics[$value])) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
        }
    }
}
