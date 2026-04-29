<?php

namespace Oro\Bundle\IntegrationBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\IntegrationBundle\Api\Repository\WebhookTopicRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads webhook topic.
 */
class LoadWebhookTopic implements ProcessorInterface
{
    public function __construct(
        private readonly WebhookTopicRepository $webhookTopicRepository
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        if ($context->hasResult()) {
            // data already retrieved
            return;
        }

        $topic = $this->webhookTopicRepository->findWebhookTopic($context->getId());
        if (null !== $topic) {
            $context->setResult($topic);
        }
    }
}
