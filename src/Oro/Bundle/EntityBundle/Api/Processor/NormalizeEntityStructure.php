<?php

namespace Oro\Bundle\EntityBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\EntityBundle\Api\EntityStructureNormalizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts an EntityStructure object to an array.
 */
class NormalizeEntityStructure implements ProcessorInterface
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

        $context->setResult(
            $this->normalizer->normalize($data, $context->getConfig())
        );

        // skip default normalization
        $context->skipGroup('normalize_data');
    }
}
