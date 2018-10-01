<?php

namespace Oro\Bundle\EntityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\EntityBundle\Api\EntityStructureNormalizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts the list of EntityStructure objects to an array.
 */
class NormalizeEntityStructures implements ProcessorInterface
{
    /** @var EntityStructureNormalizer */
    private $normalizer;

    /**
     * @param EntityStructureNormalizer $normalizer
     */
    public function __construct(EntityStructureNormalizer $normalizer)
    {
        $this->normalizer = $normalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
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

        $config = $context->getConfig();
        $normalizedData = [];
        foreach ($data as $key => $value) {
            $normalizedData[$key] = $this->normalizer->normalize($value, $config);
        }
        $context->setResult($normalizedData);

        // skip default normalization
        $context->skipGroup('normalize_data');
    }
}
