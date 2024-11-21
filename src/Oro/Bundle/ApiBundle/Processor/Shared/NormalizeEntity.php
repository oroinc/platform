<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts the entity to an array using the ObjectNormalizer.
 */
class NormalizeEntity implements ProcessorInterface
{
    private ObjectNormalizer $objectNormalizer;

    public function __construct(ObjectNormalizer $objectNormalizer)
    {
        $this->objectNormalizer = $objectNormalizer;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        if (!$context->hasResult()) {
            // no result
            return;
        }

        $data = $context->getResult();
        if (empty($data)) {
            // nothing to do because of empty result
            return;
        }

        $data = $this->objectNormalizer->normalizeObjects(
            [$data],
            $context instanceof FormContext ? $context->getNormalizedConfig() : $context->getConfig(),
            $context->getNormalizationContext()
        );
        $context->setResult(reset($data));
    }
}
