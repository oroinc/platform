<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Normalizer\ObjectNormalizer;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Converts the entity to an array using the ObjectNormalizer.
 */
class NormalizeEntity implements ProcessorInterface
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

        $context->setResult(
            $this->objectNormalizer->normalizeObject(
                $data,
                $config,
                [
                    Context::ACTION       => $context->getAction(),
                    Context::VERSION      => $context->getVersion(),
                    Context::REQUEST_TYPE => $context->getRequestType()
                ]
            )
        );
    }
}
