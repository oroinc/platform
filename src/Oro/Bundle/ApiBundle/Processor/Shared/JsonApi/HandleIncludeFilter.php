<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ExpandRelatedEntitiesConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

/**
 * Checks whether the "include" filter exists and if so,
 * adds the corresponding configuration extra into the Context.
 * This filter is used to specify which related entities should be returned.
 */
class HandleIncludeFilter implements ProcessorInterface
{
    /** @var ValueNormalizer */
    protected $valueNormalizer;

    /**
     * @param ValueNormalizer $valueNormalizer
     */
    public function __construct(ValueNormalizer $valueNormalizer)
    {
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasConfigExtra(ExpandRelatedEntitiesConfigExtra::NAME)) {
            // the "include" filter is already processed
            return;
        }

        $filterValue = $context->getFilterValues()->get(AddIncludeFilter::FILTER_KEY);
        if (null === $filterValue) {
            // expanding of related entities was not requested
            return;
        }

        $includes = $this->valueNormalizer->normalizeValue(
            $filterValue->getValue(),
            DataType::STRING,
            $context->getRequestType(),
            true
        );
        if (!empty($includes)) {
            $context->addConfigExtra(new ExpandRelatedEntitiesConfigExtra((array)$includes));
        }
    }
}
