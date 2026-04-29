<?php

namespace Oro\Bundle\IntegrationBundle\Model\WebhookResponseProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Delegates the processing of the webhook response to a specific processor.
 */
class WebhookResponseProcessor implements WebhookResponseProcessorInterface
{
    /**
     * @param iterable|WebhookResponseProcessorInterface[] $processors
     */
    public function __construct(
        private iterable $processors
    ) {
    }

    public function process(
        ResponseInterface $response,
        WebhookProducerSettings $webhook,
        string $messageId,
        bool $throwExceptionOnError = false
    ): bool {
        foreach ($this->processors as $processor) {
            if ($processor->supports($response, $webhook)) {
                return $processor->process($response, $webhook, $messageId, $throwExceptionOnError);
            }
        }

        return true;
    }

    public function supports(ResponseInterface $response, WebhookProducerSettings $webhook): bool
    {
        return true;
    }
}
