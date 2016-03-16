<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Config\ActionsConfig;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class EntityTypeSecurityCheck implements ProcessorInterface
{
    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $permission;

    /**
     * @param SecurityFacade $securityFacade
     * @param DoctrineHelper $doctrineHelper
     * @param string         $permission
     */
    public function __construct(
        SecurityFacade $securityFacade,
        DoctrineHelper $doctrineHelper,
        $permission
    ) {
        $this->securityFacade = $securityFacade;
        $this->doctrineHelper = $doctrineHelper;
        $this->permission = $permission;
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

        $action = $context->getAction();

        /** @var ActionsConfig $actions */
        $actions = $context->getConfigOf('actions');

        // we should not check access for this action
        if (!$actions->isAclProtected($action)) {
            return;
        }

        $isGranted = $actions->getAclResource($action)
            ? $this->securityFacade->isGranted($actions->getAclResource($action))
            : $this->securityFacade->isGranted($this->permission, new ObjectIdentity('entity', $entityClass));


        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }
}
