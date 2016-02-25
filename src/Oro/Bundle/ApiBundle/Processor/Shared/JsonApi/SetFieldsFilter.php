<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FieldsFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityClassTransformerInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class SetFieldsFilter implements ProcessorInterface
{
    const FILTER_KEY          = 'fields';
    const FILTER_KEY_TEMPLATE = 'fields[%s]';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityClassTransformerInterface */
    protected $entityClassTransformer;

    /**
     * @param DoctrineHelper                  $doctrineHelper
     * @param EntityClassTransformerInterface $entityClassTransformer
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityClassTransformerInterface $entityClassTransformer
    ) {
        $this->doctrineHelper         = $doctrineHelper;
        $this->entityClassTransformer = $entityClassTransformer;
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

        $filters = $context->getFilters();
        if ($filters->has(self::FILTER_KEY)) {
            // filters have been already set
            return;
        }

        $this->addFilter($filters, $entityClass);

        $associations = $context->getMetadata()->getAssociations();
        foreach ($associations as $association) {
            $targetClasses = $association->getAcceptableTargetClassNames();
            foreach ($targetClasses as $targetClass) {
                $this->addFilter($filters, $targetClass);
            }
        }
    }

    /**
     * @param FilterCollection $filters
     * @param string           $entityClass
     */
    protected function addFilter(FilterCollection $filters, $entityClass)
    {
        $entityType = $this->entityClassTransformer->transform($entityClass, false);
        if ($entityType) {
            $filter = new FieldsFilter(
                DataType::STRING,
                sprintf('A list of fields for the \'%s\' entity to be returned.', $entityType)
            );
            $filter->setArrayAllowed(true);

            $filters->add(
                sprintf(self::FILTER_KEY_TEMPLATE, $entityType),
                $filter
            );
        }
    }
}
