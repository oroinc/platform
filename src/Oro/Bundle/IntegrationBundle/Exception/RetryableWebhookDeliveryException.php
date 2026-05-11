<?php

namespace Oro\Bundle\IntegrationBundle\Exception;

/**
 * Exception that indicates that the delivery of a webhook should be retried later.
 */
class RetryableWebhookDeliveryException extends WebhookDeliveryException
{
    /**
     * @var int|null delay in milliseconds
     */
    private ?int $delay = null;

    public function setDelay(?int $delay): void
    {
        $this->delay = $delay;
    }

    public function getDelay(): ?int
    {
        return $this->delay;
    }
}
