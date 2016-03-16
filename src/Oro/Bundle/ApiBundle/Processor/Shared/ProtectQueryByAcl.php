<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\SecurityBundle\Metadata\AclAnnotationProvider;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class ProtectQueryByAcl implements ProcessorInterface
{
    /** @var AclHelper */
    protected $aclHelper;

    /** @var AclAnnotationProvider */
    protected $annotationProvider;

    /** @var string */
    protected $permission;

    /**
     * @param AclHelper $aclHelper
     * @param string    $permission
     */
    public function __construct(AclHelper $aclHelper, AclAnnotationProvider $annotationProvider, $permission)
    {
        $this->aclHelper = $aclHelper;
        $this->annotationProvider = $annotationProvider;
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

        $action = $context->getAction();

        /** @var ActionsConfig $actions */
        $actions = $context->getConfigOf('actions');

        // we should not check access for this action
        if (!$actions->isAclProtected($action)) {
            return;
        }

        $permission = $this->permission;

        if ($actions->getAclResource($action) !== null) {
            $aclResource = $this->annotationProvider->findAnnotationById($actions->getAclResource($action));
            // acl resource was not found
            if (!$aclResource) {
                return;
            }

            // given ACL Resource cannot be applied
            if ($aclResource->getType() !== 'entity'
                || $aclResource->getClass() !== $context->getClassName()
                || !$aclResource->getPermission()
            ) {
                return;
            }

            $permission = $aclResource->getPermission();
        }

        $this->aclHelper->applyAclToCriteria(
            $context->getClassName(),
            $context->getCriteria(),
            $permission
        );
    }
}
