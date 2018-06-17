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
 * @todo: remove this processor in BAP-10836
 */
class EntityObjectsSecurityCheck implements ProcessorInterface
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var string */
    private $permission;

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
        /** @var Context $context */

        $entities = $context->getResult();
        if (!is_array($entities) && !$entities instanceof \Traversable) {
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
