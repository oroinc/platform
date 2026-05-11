<?php

namespace Oro\Bundle\IntegrationBundle\Model\WebhookRequestProcessor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookProducerSettings;
use Psr\Container\ContainerInterface;

/**
 * Delegates the processing of the webhook request data model to a specific processor.
 */
class WebhookRequestProcessor implements WebhookRequestProcessorInterface
{
    public function __construct(
        private ContainerInterface $serviceLocator
    ) {
    }

    public function process(
        WebhookRequestContext $context,
        WebhookProducerSettings $webhook,
        string $messageId,
        bool $throwExceptionOnError = false
    ): void {
        $format = $webhook->getFormat();
        if (!$this->serviceLocator->has($format)) {
            return;
        }

        /** @var WebhookRequestProcessorInterface $processor */
        $processor = $this->serviceLocator->get($format);
        $processor->process($context, $webhook, $messageId, $throwExceptionOnError);
    }
}
