<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\Common\Collections\Criteria;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class ProtectQueryByAcl implements ProcessorInterface
{
    /** @var AclHelper */
    protected $aclHelper;

    /** @var string */
    protected $permission;

    /**
     * @param AclHelper $aclHelper
     * @param string    $permission
     */
    public function __construct(AclHelper $aclHelper, $permission)
    {
        $this->aclHelper  = $aclHelper;
        $this->permission = $permission;
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

        $entityClass = $context->getClassName();
        if (!$entityClass) {
            // an entity type is not specified
            return;
        }

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            $criteria = new Criteria();
            $context->setCriteria($criteria);
        }

        $this->aclHelper->applyAclToCriteria($entityClass, $criteria, $this->permission);
    }
}
