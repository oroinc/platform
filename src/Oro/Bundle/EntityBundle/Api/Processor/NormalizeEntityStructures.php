<?php

namespace Oro\Bundle\EntityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\ApiActionGroup;
use Oro\Bundle\EntityBundle\Api\EntityStructureNormalizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts the list of EntityStructure objects to an array.
 */
class NormalizeEntityStructures implements ProcessorInterface
{
    private EntityStructureNormalizer $normalizer;

    public function __construct(EntityStructureNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
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

        $requestType = $context->getRequestType();
        $normalizedData = [];
        foreach ($data as $key => $value) {
            $normalizedData[$key] = $this->normalizer->normalize($value, $requestType);
        }
        $context->setResult($normalizedData);

        // skip default normalization
        $context->skipGroup(ApiActionGroup::NORMALIZE_DATA);
    }
}
