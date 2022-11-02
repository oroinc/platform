<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\CriteriaConnector;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Applies the Criteria object to ORM QueryBuilder object.
 */
class ApplyCriteria implements ProcessorInterface
{
    /** @var CriteriaConnector */
    private $criteriaConnector;

    public function __construct(CriteriaConnector $criteriaConnector)
    {
        $this->criteriaConnector = $criteriaConnector;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var Context $context */

        $criteria = $context->getCriteria();
        if (null !== $criteria) {
            $query = $context->getQuery();
            if ($query instanceof QueryBuilder) {
                $this->criteriaConnector->applyCriteria($query, $criteria);
                $context->setCriteria();
            }
        }
    }
}
