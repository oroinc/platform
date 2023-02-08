<?php

namespace Oro\Bundle\ApiBundle\Processor\Shared;

use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Validates whether an access to the entity objects is granted
 * and remove all entities that cannot be deleted from the result.
 * The permission type is provided in $permission argument of the class constructor.
 */
class ValidateEntityObjectsAccess implements ProcessorInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private string $permission;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        string $permission
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->permission = $permission;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var Context $context */

        $entities = $context->getResult();
        if (!\is_array($entities) && !$entities instanceof \Traversable) {
            return;
        }

        $validatedEntities = [];
        $config = $context->getConfig();
        foreach ($entities as $entity) {
            $isGranted = true;
            if (null !== $config && $config->hasAclResource()) {
                $aclResource = $config->getAclResource();
                if ($aclResource) {
                    $isGranted = $this->authorizationChecker->isGranted($aclResource, $entity);
                }
            } else {
                $isGranted = $this->authorizationChecker->isGranted($this->permission, $entity);
            }
            if ($isGranted) {
                $validatedEntities[] = $entity;
            }
        }
        $context->setResult($validatedEntities);
    }
}
