<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\IncludeFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Adds "include" filter.
 * This filter can be used to specify which related entities should be returned.
 */
class SetIncludeFilter implements ProcessorInterface
{
    const FILTER_KEY = 'include';

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

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $indexedAssociations = $this->doctrineHelper->getIndexedAssociations(
            $this->doctrineHelper->getEntityMetadata($entityClass)
        );
        if (!$indexedAssociations) {
            // no associations - no sense to add include filters
            return;
        }

        $filters = $context->getFilters();
        if ($filters->has(self::FILTER_KEY)) {
            // filters have been already set
            return;
        }

        $includeFilter = new IncludeFilter(
            DataType::STRING,
            'A list of related entities to be included. Comma-separated paths, e.g. \'comments,comments.author\'.'
        );
        $includeFilter->setArrayAllowed(true);

        $filters->add(self::FILTER_KEY, $includeFilter);
    }
}
