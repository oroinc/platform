<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Add ACL restrictions to the Criteria object.
 */
class ProtectQueryByAcl implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var AclHelper */
    protected $aclHelper;

    /** @var AclAnnotationProvider */
    protected $aclAnnotationProvider;

    /** @var string */
    protected $permission;

    /**
     * @param DoctrineHelper        $doctrineHelper
     * @param AclHelper             $aclHelper
     * @param AclAnnotationProvider $aclAnnotationProvider
     * @param string                $permission
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        AclHelper $aclHelper,
        AclAnnotationProvider $aclAnnotationProvider,
        $permission
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->aclHelper = $aclHelper;
        $this->aclAnnotationProvider = $aclAnnotationProvider;
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

        $criteria = $context->getCriteria();
        if (null === $criteria) {
            // the criteria object does not exist
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $config = $context->getConfig();

        $permission = null;
        if (!$config || !$config->hasAclResource()) {
            $permission = $this->permission;
        } else {
            $aclResource = $config->getAclResource();
            if ($aclResource) {
                $aclAnnotation = $this->aclAnnotationProvider->findAnnotationById($aclResource);
                if ($aclAnnotation
                    && $aclAnnotation->getType() === 'entity'
                    && $aclAnnotation->getClass() === $entityClass
                ) {
                    $permission = $aclAnnotation->getPermission();
                }
            }
        }

        if ($permission) {
            $this->aclHelper->applyAclToCriteria($entityClass, $criteria, $permission);
        }
    }
}
