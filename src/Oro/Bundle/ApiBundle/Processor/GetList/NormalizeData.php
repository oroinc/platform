<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;

class NormalizeData implements ProcessorInterface
{
    /** @var ObjectNormalizer */
    protected $objectNormalizer;

    /**
     * @param ObjectNormalizer $objectNormalizer
     */
    public function __construct(ObjectNormalizer $objectNormalizer)
    {
        $this->objectNormalizer = $objectNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var GetListContext $context */

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
            $normalizedData[$key] = $this->objectNormalizer->normalizeObject($value, $config);
        }
        $context->setResult($normalizedData);
    }
}
