<?php

namespace Oro\Bundle\EmailBundle\Workflow\Action;

use Oro\Bundle\EmailBundle\Model\Recipient;
use Oro\Component\Action\Exception\InvalidParameterException;

/**
 * Workflow action that sends emails based on passed templates
 */
abstract class AbstractSendEmailTemplate extends AbstractSendEmail
{
    protected array $options = [];

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options): self
    {
        $this->assertFrom($options);

        if (empty($options['to']) && empty($options['recipients'])) {
            throw new InvalidParameterException('Need to specify "to" or "recipients" parameters');
        }

        $this->normalizeToOption($options);
        $this->normalizeRecipientsOption($options);

        if (empty($options['template'])) {
            throw new InvalidParameterException('Template parameter is required');
        }

        if (empty($options['entity'])) {
            throw new InvalidParameterException('Entity parameter is required');
        }

        $this->options = $options;

        return $this;
    }

    protected function getRecipients(object|array $context, array $options): array
    {
        $recipients = parent::getRecipients($context, $options);
        $recipients = array_map(static fn (string $address) => new Recipient($address), $recipients);

        foreach ($this->options['recipients'] as $recipient) {
            if ($recipient) {
                $recipients[] = $this->contextAccessor->getValue($context, $recipient);
            }
        }

        return $recipients;
    }

    /**
     * @throws InvalidParameterException
     */
    protected function normalizeRecipientsOption(array &$options): void
    {
        if (empty($options['recipients'])) {
            $options['recipients'] = [];
        }

        if (!is_array($options['recipients'])) {
            throw new InvalidParameterException(
                \sprintf('Recipients parameter must be an array, %s given', \gettype($options['recipients']))
            );
        }
    }
}
