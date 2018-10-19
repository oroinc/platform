<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Validates whether an access to the entity object is granted.
 * The permission type is provided in $permission argument of the class constructor.
 */
class EntityObjectSecurityCheck implements ProcessorInterface
{
    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var string */
    protected $permission;

    /**
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param string                        $permission
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        $permission
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->permission = $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var SingleItemContext $context */

        $isGranted = true;
        $entity = $context->getResult();
        if ($entity) {
            $config = $context->getConfig();
            if (null !== $config && $config->hasAclResource()) {
                $aclResource = $config->getAclResource();
                if ($aclResource) {
                    $isGranted = $this->authorizationChecker->isGranted($aclResource, $entity);
                }
            } else {
                $isGranted = $this->authorizationChecker->isGranted($this->permission, $entity);
            }
        }

        if (!$isGranted) {
            throw new AccessDeniedException();
        }
    }
}
