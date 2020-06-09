<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Config\Extra\FilterFieldsConfigExtra;
use Oro\Bundle\ApiBundle\Filter\FilterNamesRegistry;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Checks whether the "fields" filters exist,
 * and if so, adds the corresponding configuration extra into the context.
 * These filters are used to specify which fields of primary
 * or related entities should be returned.
 */
class HandleFieldsFilter implements ProcessorInterface
{
    /** @var FilterNamesRegistry */
    private $filterNamesRegistry;

    /** @var ValueNormalizer */
    private $valueNormalizer;

    /**
     * @param FilterNamesRegistry $filterNamesRegistry
     * @param ValueNormalizer     $valueNormalizer
     */
    public function __construct(FilterNamesRegistry $filterNamesRegistry, ValueNormalizer $valueNormalizer)
    {
        $this->filterNamesRegistry = $filterNamesRegistry;
        $this->valueNormalizer = $valueNormalizer;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasConfigExtra(FilterFieldsConfigExtra::NAME)) {
            // the "fields" filters are already processed
            return;
        }

        $requestType = $context->getRequestType();
        $filterGroupName = $this->filterNamesRegistry
            ->getFilterNames($requestType)
            ->getFieldsFilterGroupName();
        if (!$filterGroupName) {
            // the "fields" filter is not supported
            return;
        }

        $fields = [];
        $filterValues = $context->getFilterValues()->getGroup($filterGroupName);
        foreach ($filterValues as $filterValue) {
            try {
                $normalizedValue = $this->valueNormalizer->normalizeValue(
                    $filterValue->getValue(),
                    DataType::STRING,
                    $requestType,
                    true
                );
                if ($normalizedValue) {
                    $fields[$filterValue->getPath()] = (array)$normalizedValue;
                }
            } catch (\Exception $e) {
                $context->addError(
                    Error::createValidationError(Constraint::FILTER)
                        ->setInnerException($e)
                        ->setSource(ErrorSource::createByParameter($filterValue->getSourceKey()))
                );
            }
        }
        if (!$fields || $context->hasErrors()) {
            // filtering of fields was not requested or detected errors in filter values
            return;
        }

        $context->addConfigExtra(new FilterFieldsConfigExtra($fields));
    }
}
