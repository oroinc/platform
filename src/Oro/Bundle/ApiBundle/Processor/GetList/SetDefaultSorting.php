<?php

namespace Oro\Bundle\ApiBundle\Processor\GetList;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class SetDefaultSorting implements ProcessorInterface
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

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            $criteria = new Criteria();
            $context->setCriteria($criteria);
        }

        $orderings = $criteria->getOrderings();
        if (empty($orderings)) {
            $defaultOrderBy = $this->getDefaultOrderBy($entityClass);
            if ($defaultOrderBy) {
                $criteria->orderBy($defaultOrderBy);
            }
        }
    }

    /**
     * Gets default ORDER BY
     *
     * @param string $entityClass
     *
     * @return array|null
     */
    protected function getDefaultOrderBy($entityClass)
    {
        $ids = $this->doctrineHelper->getEntityMetadata($entityClass)->getIdentifierFieldNames();
        if (empty($ids)) {
            return null;
        }

        $orderBy = [];
        foreach ($ids as $pk) {
            $orderBy[$pk] = Criteria::ASC;
        }

        return $orderBy;
    }
}
