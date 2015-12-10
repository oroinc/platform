<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;

class EntityTypeSecurityCheck implements ProcessorInterface
{
    /** @var AuthorizationCheckerInterface */
    protected $securityContext;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var string */
    protected $permission;

    /**
     * @param AuthorizationCheckerInterface $securityContext
     * @param DoctrineHelper                $doctrineHelper
     * @param string                        $permission
     */
    public function __construct(
        AuthorizationCheckerInterface $securityContext,
        DoctrineHelper $doctrineHelper,
        $permission
    ) {
        $this->securityContext = $securityContext;
        $this->doctrineHelper  = $doctrineHelper;
        $this->permission      = $permission;
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

        if (!$this->securityContext->isGranted($this->permission, new ObjectIdentity('entity', $entityClass))) {
            throw new AccessDeniedException();
        }
    }
}
