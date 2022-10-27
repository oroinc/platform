<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Add ACL restrictions to ORM query.
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

        $query = $context->getQuery();
        if (!($query instanceof QueryBuilder || $query instanceof Query)) {
            // ACL helper supports only QueryBuilder or Query
            return;
        }

        $entityClass = $context->getClassName();
        if (!$this->doctrineHelper->isManageableEntityClass($entityClass)) {
            // only manageable entities are supported
            return;
        }

        $permission = null;
        $config = $context->getConfig();
        if (null !== $config && $config->hasAclResource()) {
            $aclResource = $config->getAclResource();
            if ($aclResource) {
                $permission = $this->getEntityPermissionByAclResource($aclResource, $entityClass);
            }
        } else {
            $permission = $this->permission;
        }
        if ($permission) {
            $context->setQuery($this->aclHelper->apply($query, $permission));
        }
    }

    /**
     * @param string $aclResource
     * @param string $entityClass
     *
     * @return string|null
     */
    private function getEntityPermissionByAclResource($aclResource, $entityClass)
    {
        $permission = null;

        $aclAnnotation = $this->aclAnnotationProvider->findAnnotationById($aclResource);
        if ($aclAnnotation
            && $aclAnnotation->getType() === 'entity'
            && $aclAnnotation->getClass() === $entityClass
        ) {
            $permission = $aclAnnotation->getPermission();
        }

        return $permission;
    }
}
