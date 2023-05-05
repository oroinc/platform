<?php

namespace Oro\Bundle\AttachmentBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Checks if it is allowed to view File entity.
 */
class ValidateFileViewAccess implements ProcessorInterface
{
    private AuthorizationCheckerInterface $authorizationChecker;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var SingleItemContext $context */

        if (!$this->authorizationChecker->isGranted(
            'VIEW',
            new ObjectIdentity($context->getId(), $context->getClassName())
        )) {
            throw new AccessDeniedException('No access to the entity.');
        }
    }
}
