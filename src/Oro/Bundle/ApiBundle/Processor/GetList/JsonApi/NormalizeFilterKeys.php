<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList\JsonApi;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Model\Label;
use Oro\Bundle\ApiBundle\Processor\GetList\GetListContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

/**
 * Encloses filters keys by the "filter[%s]" pattern.
 * Replaces the filter key for the identifier field with "filter[id]".
 */
class NormalizeFilterKeys implements ProcessorInterface
{
    const FILTER_KEY_TEMPLATE = 'filter[%s]';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(DoctrineHelper $doctrineHelper, TranslatorInterface $translator)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->translator     = $translator;
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
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $filterCollection = $context->getFilters();
        $idFieldName      = $this->getIdFieldName($context->getClassName());

        $filters = $filterCollection->all();
        foreach ($filters as $filterKey => $filter) {
            $filterCollection->remove($filterKey);
            if ($filter instanceof ComparisonFilter && $filter->getField() === $idFieldName) {
                $filterKey = 'id';
                $filter->setDescription($this->getIdFieldDescription());
            }
            $filterCollection->add(
                sprintf(self::FILTER_KEY_TEMPLATE, $filterKey),
                $filter
            );
        }
    }

    /**
     * @param string $entityClass
     *
     * @return string|null
     */
    protected function getIdFieldName($entityClass)
    {
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            return null;
        }

        $idFieldNames = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);

        return reset($idFieldNames);
    }

    /**
     * @return string
     */
    protected function getIdFieldDescription()
    {
        $label = new Label('oro.entity.identifier_field');

        return $label->trans($this->translator);
    }
}
