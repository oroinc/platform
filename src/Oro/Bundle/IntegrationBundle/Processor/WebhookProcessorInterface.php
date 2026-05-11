<?php

namespace Oro\Bundle\IntegrationBundle\Processor;

use Oro\Bundle\IntegrationBundle\Entity\WebhookConsumerSettings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Process webhook notification.
 */
interface WebhookProcessorInterface
{
    public function process(WebhookConsumerSettings $webhook, Request $request): ?Response;

    public static function getName(): string;
}
