<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared\JsonApi;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\FieldsFilter;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityClassTransformerInterface;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class SetFieldsFilter implements ProcessorInterface
{
    const FILTER_KEY          = 'fields';
    const FILTER_KEY_TEMPLATE = 'fields[%s]';
    const FILTER_KEY_DESCRIPTION
        = 'Return only specific fields in the response on a per-type basis by including a fields[TYPE] parameter.';

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

        $fieldFilter = new FieldsFilter(
            DataType::STRING,
            self::FILTER_KEY_DESCRIPTION
        );
        $fieldFilter->setArrayAllowed(true);

        $filters->add(
            sprintf(self::FILTER_KEY_TEMPLATE, $this->entityClassTransformer->transform($entityClass)),
            $fieldFilter
        );

        $associations = $context->getMetadata()->getAssociations();
        if (!$associations) {
            // no associations - no sense to add associations fields filters
            return;
        }

        $associationKeys = array_keys($associations);
        foreach ($associationKeys as $association) {
            $filters->add(
                sprintf(self::FILTER_KEY_TEMPLATE, $association),
                $fieldFilter
            );
        }
    }
}
