<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

use Oro\Bundle\ApiBundle\Processor\ActionProcessorBagInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\Get\GetContext;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * Converted entity to the event data (array) applicable for the webhook notification.
 *
 * Use the API data to eliminate differences between the webhook and the API calls.
 * This should simplify the integration logic.
 */
abstract class AbstractWebhookEventDataProvider implements WebhookEventDataProviderInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private ActionProcessorBagInterface $actionProcessorBag
    ) {
    }

    abstract protected function getRequestTypes(): array;
    abstract protected function getFallbackData(string $className, int|string $id): array;

    public function getEventData(string $entityClass, int|string $entityId): array
    {
        try {
            $processor = $this->actionProcessorBag->getProcessor(ApiAction::GET);
            /** @var GetContext $context */
            $context = $processor->createContext();
            $this->prepareContext($context, $entityClass, $entityId);

            $processor->process($context);

            return $this->buildResponse($context);
        } catch (\Throwable $e) {
            $this->logger?->error(
                'Failed to serialize entity for webhook',
                [
                    'exception' => $e,
                    'entity_class' => $entityClass,
                    'entity_id' => $entityId
                ]
            );

            return $this->getFallbackData($entityClass, $entityId);
        }
    }

    protected function prepareContext(Context $context, string $entityClass, int|string $entityId): void
    {
        foreach ($this->getRequestTypes() as $aspect) {
            $context->getRequestType()->add($aspect);
        }
        $context->setMainRequest(true);
        $context->setClassName($entityClass);
        $context->setId($entityId);
    }

    private function buildResponse(Context $context): array
    {
        $result = $context->getResult();
        if (null === $result || $this->isErrorResponse($context)) {
            $this->logger?->error(
                'Failed to serialize entity for webhook',
                [
                    'entity_class' => $context->getClassName(),
                    'entity_id' => $context->getId()
                ]
            );

            // Fallback to the entity id if the API request failed
            return $this->getFallbackData($context->getClassName(), $context->getId());
        }

        return $result;
    }

    protected function isErrorResponse(Context $context): bool
    {
        $responseStatusCode = $context->getResponseStatusCode();

        return null !== $responseStatusCode && $responseStatusCode >= Response::HTTP_BAD_REQUEST;
    }
}
