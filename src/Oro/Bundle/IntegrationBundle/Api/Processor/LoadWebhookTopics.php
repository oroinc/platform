<?php

namespace Oro\Bundle\IntegrationBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\IntegrationBundle\Api\Repository\WebhookTopicRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads available webhook topics.
 */
class LoadWebhookTopics implements ProcessorInterface
{
    public function __construct(
        private readonly WebhookTopicRepository $webhookTopicRepository
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $context->setResult($this->webhookTopicRepository->getWebhookTopics());
    }
}
