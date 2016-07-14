<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * A base class that can be used to create a processor to set default sorting for different kind of requests.
 * The default sorting is "identifier field ASC".
 */
abstract class SetDefaultSorting implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }
        if (!$context->getConfig()->isSortingEnabled()) {
            // a sorting is disabled
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $this->addSortFilter($context->getFilters(), $entityClass);
    }

    /**
     * @param FilterCollection $filters
     * @param string           $entityClass
     */
    protected function addSortFilter(FilterCollection $filters, $entityClass)
    {
        $sortFilterKey = $this->getSortFilterKey();
        if (!$filters->has($sortFilterKey)) {
            $filters->add(
                $sortFilterKey,
                new SortFilter(
                    DataType::ORDER_BY,
                    $this->getSortFilterDescription(),
                    function () use ($entityClass) {
                        return $this->getDefaultValue($entityClass);
                    },
                    function ($value) {
                        return $this->convertDefaultValueToString($value);
                    }
                )
            );
        }
    }

    /**
     * @return string
     */
    abstract protected function getSortFilterKey();

    /**
     * @return string
     */
    protected function getSortFilterDescription()
    {
        return 'Result sorting. Comma-separated fields, e.g. \'field1,-field2\'.';
    }

    /**
     * @param string $entityClass
     *
     * @return string
     */
    protected function getDefaultValue($entityClass)
    {
        return $this->doctrineHelper->getOrderByIdentifier($entityClass);
    }

    /**
     * @param array|null $value
     *
     * @return string
     */
    protected function convertDefaultValueToString($value)
    {
        $result = [];
        if (null !== $value) {
            foreach ($value as $field => $order) {
                $result[] = (Criteria::DESC === $order ? '-' : '') . $field;
            }
        }

        return implode(',', $result);
    }
}
