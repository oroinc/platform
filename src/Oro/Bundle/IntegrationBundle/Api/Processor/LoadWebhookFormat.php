<?php

namespace Oro\Bundle\IntegrationBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\IntegrationBundle\Api\Repository\WebhookFormatRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Loads webhook format.
 */
class LoadWebhookFormat implements ProcessorInterface
{
    public function __construct(
        private readonly WebhookFormatRepository $webhookFormatRepository
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

        $format = $this->webhookFormatRepository->findWebhookFormat($context->getId());
        if (null !== $format) {
            $context->setResult($format);
        }
    }
}
