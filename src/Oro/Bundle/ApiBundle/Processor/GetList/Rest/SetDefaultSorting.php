<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\Rest;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\SortFilter;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class SetDefaultSorting implements ProcessorInterface
{
    const SORT_FILTER_KEY = 'sort';

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
        /** @var GetListContext $context */

        if ($context->hasQuery()) {
            // a query is already built
            return;
        }

        $entityClass = $context->getClassName();
        if (!$entityClass || !$this->doctrineHelper->isManageableEntity($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $filters = $context->getFilters();
        if (!$filters->has(self::SORT_FILTER_KEY)) {
            $filters->add(
                self::SORT_FILTER_KEY,
                new SortFilter(
                    DataType::ORDER_BY,
                    'Result sorting. One or several fields separated by comma, for example \'field1,-field2\'.',
                    function () use ($entityClass) {
                        return $this->doctrineHelper->getOrderByIdentifier($entityClass);
                    },
                    function ($value) {
                        $result = [];
                        if (null !== $value) {
                            foreach ($value as $field => $order) {
                                $result[] = (Criteria::DESC === $order ? '-' : '') . $field;
                            }
                        }

                        return implode(',', $result);
                    }
                )
            );
        }
    }
}
