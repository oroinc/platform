<?php

namespace Oro\Bundle\IntegrationBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\IntegrationBundle\Api\Repository\WebhookFormatRepository;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Expands data for "format" association of WebhookProducerSettings entity.
 */
class ExpandWebhookFormat implements ProcessorInterface
{
    public function __construct(
        private readonly WebhookFormatRepository $webhookFormatRepository,
        private readonly ObjectNormalizer $objectNormalizer
    ) {
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $config = $context->getConfig();
        if (null === $config) {
            return;
        }

        $formatFieldName = $context->getResultFieldName('format');
        $formatField = $config->getField($formatFieldName);
        if (null === $formatField || $formatField->isCollapsed()) {
            return;
        }

        $data = $context->getData();
        $formats = $this->getWebhookFormats($data, $formatFieldName);
        if (!$formats) {
            return;
        }

        $normalizedTypes = $this->getNormalizedWebhookFormats(
            $formats,
            $formatField->getTargetEntity(),
            $context->getNormalizationContext()
        );
        foreach ($data as $key => $item) {
            if (isset($item[$formatFieldName])) {
                $format = $item[$formatFieldName];
                if ($format && isset($normalizedTypes[$format])) {
                    $data[$key][$formatFieldName] = $normalizedTypes[$format];
                }
            }
        }
        $context->setData($data);
    }

    private function getWebhookFormats(array $data, string $formatFieldName): array
    {
        $formats = [];
        foreach ($data as $item) {
            if (isset($item[$formatFieldName])) {
                $format = $item[$formatFieldName];
                if ($format && !isset($formats[$format])) {
                    $formats[$format] = true;
                }
            }
        }

        return array_keys($formats);
    }

    private function getNormalizedWebhookFormats(
        array $formats,
        EntityDefinitionConfig $config,
        array $context
    ): array {
        $toNormalize = [];
        foreach ($formats as $format) {
            $webhookFormat = $this->webhookFormatRepository->findWebhookFormat($format);
            if (null !== $webhookFormat) {
                $toNormalize[$format] = $webhookFormat;
            }
        }

        return $this->objectNormalizer->normalizeObjects($toNormalize, $config, $context);
    }
}
