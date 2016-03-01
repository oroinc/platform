<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

/**
 * Sets "fields[]" filter into the Context.
 */
class NormalizeFieldsParameter implements ProcessorInterface
{
    const FILTER_KEY = 'fields';

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

        if (!$context->hasConfigExtra(FilterFieldsConfigExtra::NAME)) {
            $fields       = [];
            $filterValues = $context->getFilterValues()->getAll(self::FILTER_KEY);
            foreach ($filterValues as $filterValue) {
                $fields[$filterValue->getPath()] = (array)$this->valueNormalizer->normalizeValue(
                    $filterValue->getValue(),
                    DataType::STRING,
                    $context->getRequestType(),
                    true
                );
            }
            if (!empty($fields)) {
                $context->addConfigExtra(new FilterFieldsConfigExtra($fields));
            }
        }
    }
}
