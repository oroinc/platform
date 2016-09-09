<?php

namespace Oro\Bundle\ApiBundle\Processor\Subresource\Shared;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * Validates whether an access to the parent entity object is granted.
 * The permission type is provided in $permission argument of the class constructor.
 */
class ParentEntityObjectSecurityCheck implements ProcessorInterface
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var string */
    protected $permission;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param SecurityFacade $securityFacade
     * @param string         $permission
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        $permission
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->securityFacade = $securityFacade;
        $this->permission = $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SubresourceContext $context */

        $isGranted = true;
        $parentEntity = $context->getParentEntity();
        if ($parentEntity && $this->doctrineHelper->isManageableEntityClass($context->getParentClassName())) {
            $isGranted = $this->securityFacade->isGranted($this->permission, $parentEntity);
        }

        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }
}
